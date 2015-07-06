<?php
/*
 *
 * Copyright (c) Paid, Inc.  All rights reserved.
 *
 * $Id: ShipRateAPI.inc,v 1.22 2015/03/08 17:29:29 dsherman Exp $
 *
 * This program is part of the Paid ShipRateAPI Toolkit and is the interface class for PHP client applications 
 * that want to make calls to the rate engine.  
 *
 */

$incPath = dirname(__FILE__);
include_once("$incPath/ShipRateSocketPost.inc");
include_once("$incPath/ShipRateParserXML.inc");include_once("$incPath/ShipRateParserXMLItem.inc");

/**
 * API Main Class
 *
 * @author "Victor Didovicher" <victord@paid.com>
 * @date 2005.05.17
 * @version 1.0
 * @copyright (c) Paid, Inc.  All rights reserved.
 */
class ShipRateAPI
{
   var $rateReq;  
   var $results; 
   var $xml;   
   var $accountId;               // Access Key to Web API
   var $refCode;                 // Reference code that is logged with each call - used for auditing when 
                                 // integrator is working with different customers
   var $secureComm = true;       // Flag to control if Web Service Calls are secure or not
   var $useCURL = false;         // Flag to control if curl library is used for server connection
   var $debugComm = false;       // Flag if communications should output debug information
   var $apiURL;	               // if set allows client to override the URL to the web service
   var $maxWeights = array();    // maximum package weights per carrier service
   var $maxLengths = array();    // maximum package lengths per carrier service
   var $maxWidths = array();     // maximum package widths per carrier service
   var $maxHeights = array();    // maximum package heights per carrier service
   var $flatRateFlag = array();  // Flag to set qualification for flat rate packaging at the carrier level

   /**
   * Constructor
   * @param    string   API account Id
   * @param    string   Reference Code  that is logged with each call (optional)
   * @return   void
   **/
   function ShipRateAPI( $accountId, $refCode='' )
   {
      $this->accountId = $accountId;
      $this->refCode = $refCode;
      $this->reset();
   }

   /**
    * Generates the GetItemShipRateXS XML and invokes the web service method
    * @param     (ref)Array   will be populated with and array of ShipRate assoc arrays
    * @return    boolean      true indicates success, false indicates an error occurred
    * @access public
    */
   function GetItemShipRateXS(& $shipRateArray )
   {
      $reqXML = $this->_makeGetItemShipRateXS_XML();
      return $this->_GetItemShipRate($reqXML, $shipRateArray);
   }

   /**
    * Generates the GetItemShipRateSS XML and invokes the web service method
    * @param     (ref)Array   will be populated with and array of ShipRate assoc arrays
    * @return    boolean      true indicates success, false indicates an error occurred
    * @access    public
    */
   function GetItemShipRateSS(& $shipRateArray )
   {
      $reqXML = $this->_makeGetItemShipRateSS_XML();
      return $this->_GetItemShipRate($reqXML, $shipRateArray);
   }

   /**
    * Internal method used by the GetItemShipRate methods which does the actual call out to the web service
    * @param      (ref)Array   will be populated with and array of ShipRate assoc arrays
    * @return     boolean      true indicates success, false indicates an error occurred
    * @access     private
    */
   function _GetItemShipRate( $reqXML, & $shipRateArray )
   {
      $post = new ShipRateSocketPost();
      
      // If they overrode the secure mode, we'll set the port to 80
      if (! $this->secureComm) $post->setPort(80);
      if (isset($this->apiURL)) $post->setURL($this->apiURL);
      
      $post->setCURL($this->useCURL); 
      $post->setDebug($this->debugComm);
     
      if ($post->post($reqXML, false, $respXML, $headers, $errorMsg)) { 
        $start  = strpos($respXML, "<?");
        $length = strpos($respXML, "</Envelope>")-$start+strlen("</Envelope>");
        $respXML = substr($respXML, $start, $length);
        
        $this->xml->loadXML($respXML);
        $respXMLArray = $this->dom_to_array($this->xml->documentElement);

        if ($this->xml->getElementsByTagName("ErrorList")->length == 0) { // no errors
          $respXMLArray = $respXMLArray['Body']['ItemShipRate'];
          $shipRateArray = array_pop($respXMLArray);
          $i=0;
          foreach ($shipRateArray['ShipRate'] as $shipRate) {
            foreach ($shipRate['PackageDetail']['Package'] as $pkg) {
              if(is_array($pkg)) {
                $tmp = $pkg['PkgItem'];
                $pkg['PkgItem'] = array();
                $pkg['PkgItem'][] = $tmp;
                $shipRateArray['ShipRate'][$i]['PackageDetail']['Package'] = $pkg;
              }
            }
            $i++;
          }
          $shipRateArray = array_merge_recursive($respXMLArray, $shipRateArray);
          return true;
        } else {
          $shipRateArray = $respXMLArray['Body'];
          $tmp = array();
          foreach ($shipRateArray['ErrorList'] as $error) {
            $tmp[] = $error;
          }
          $shipRateArray['ErrorList'] = $tmp;
          return false;
        }
      } else {
         // Set an error
         $shipRateArray = $this->_createError(505, $errorMsg);
         return false;
      }
   }
   
   /**
   * Used to override the default URL to the Web Service API
   * @param    string	url to web service
   * @return   void
   * @access   public
   **/
   function setURL( $url ) {
      $this->apiURL = $url;
   }
   
   /**
   * Used to control if the communications to the web service is secure or not
   * @param    bool     true (default) will result in calls being invoked via SSL
   * @return   void
   * @access   public
   **/
   function setSecureComm( $trueFalse ) {
      $this->secureComm = is_bool($trueFalse) ? $trueFalse : true;
   }
   
   /**
   * Used to control if the communications to the web service uses CURL
   * @param    bool     true (default) will result in calls being invoked via CURL
   * @return   void
   * @access   public
   **/
   function useCURL( $trueFalse ) {
      $this->useCURL = is_bool($trueFalse) ? $trueFalse : true;
   }
   
   /**
   * Used to control if the communications should echo out debug information
   * @param    bool     true will enable debug information, (default false)
   * @return   void
   * @access   public
   **/
   function setDebugComm( $trueFalse ) {
      $this->debugComm = is_bool($trueFalse) ? $trueFalse : false;
   }
   
   /**
    * Reinitialize the class to initial state
    * @access public
    */
   function reset()
   {
      $this->rateReq = array();
      $this->rateReq['DestinationAddress'] = array();
      $this->rateReq['OriginAddressList'] = array();
      $this->rateReq['ItemList'] = array();
      $this->rateReq['CarrierList'] = array();
      $this->rateReq['Currency'] = 'USD';
      $this->rateReq['DetailLevel'] = 1;
      $this->setAccountId($this->accountId);
      $this->xml = new DOMDocument('1.0', 'utf-8');
   }
   
   /**
    * Set Header Reference Code
    * @param String
    * @access public
    */
   function setHeaderRefCode($v) { $this->refCode = ($v); }

   /**
    * Set Currency
    * @param String - Currency (USD)
    * @access public
    */
   function setCurrency($v) { $this->rateReq['Currency'] = strtoupper($v); }
   
   /**
    * Set Detail Level
    * @param Integer 
    * @access public
    */
   function setDetailLevel($v) { $this->rateReq['DetailLevel'] = ($v); }

   /**
    * Set ShipRate API Account ID
    * @param String - Your PAID ShipAPI Account ID
    * @access public
    */
   function setAccountId($id) { $this->rateReq['AccountID'] = $id; }

   /**
    * Add Origin Address
    * @param String - Postal Code
    * @param String - State or Province Code (2 Letter)
    * @param String - Country Code (2 Letter)
    * @param String - Origin Code
    * @access public
    */
   function addOriginAddress($countryCode, $postalCode, $stateOrProvinceCode='', $originCode='')
   {
      $cnt = (int) count($this->rateReq['OriginAddressList']);
      $this->rateReq['OriginAddressList'][$cnt] = array();
      $this->rateReq['OriginAddressList'][$cnt]['PostalCode'] = strtoupper($postalCode);
      $this->rateReq['OriginAddressList'][$cnt]['StateOrProvinceCode'] = strtoupper($stateOrProvinceCode);
      $this->rateReq['OriginAddressList'][$cnt]['CountryCode'] = strtoupper($countryCode);
      $this->rateReq['OriginAddressList'][$cnt]['OriginCode'] = $originCode;
   }

   /**
    * Set Destination Address
    * @param String - Postal Code
    * @param String - State or Province Code (2 Letter)
    * @param String - Country Code (2 Letter)
    * @param Bool - Residential Falg
    * @access public
    */
   function setDestinationAddress($countryCode, $postalCode, $stateOrProvinceCode='', $residentialFlag=false)
   {
      $this->rateReq['DestinationAddress']['PostalCode'] = strtoupper($postalCode);
      $this->rateReq['DestinationAddress']['StateOrProvinceCode'] = strtoupper($stateOrProvinceCode);
      $this->rateReq['DestinationAddress']['CountryCode'] = strtoupper($countryCode);
      $this->rateReq['DestinationAddress']['Residential'] = ($residentialFlag ? 1 : 0);
   }

   /**
    * Add Item with Calc Rates
    * @param String - Reference Item Code
    * @param Float  - Quantity
    * @param Float  - Length
    * @param Float  - Width
    * @param Float  - Height
    * @param String - Dimensional Units: IN | CM
    * @param Float  - Weight
    * @param String - Weight Units: LBS | KGS
    * @param Float  - Declared Value
    * @param String - Packing Method: T | S
    * @param String - Lot Size 
    * @param String - Calc Method: C | CI   
    * @access public
    */
   function addItemCalc($refCode, $qty, $weight, $wtUOM, $length, $width ,$height ,$dimUOM, $decVal, $packMethod, $lotSize=1, $calcMethod="C" )
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $this->rateReq['ItemList'][$cnt] = array();
      $this->rateReq['ItemList'][$cnt]['CalcMethod']    = $calcMethod;
      $this->rateReq['ItemList'][$cnt]['RefCode']       = $refCode;
      $this->rateReq['ItemList'][$cnt]['Quantity']      = (float) abs($qty);
      $this->rateReq['ItemList'][$cnt]['LotSize']       = (float) abs($lotSize);
      $this->rateReq['ItemList'][$cnt]['Length']        = (float) abs($length);
      $this->rateReq['ItemList'][$cnt]['Width']         = (float) abs($width);
      $this->rateReq['ItemList'][$cnt]['Height']        = (float) abs($height);
      $this->rateReq['ItemList'][$cnt]['DimUOM']        = (!strcasecmp('CM', $dimUOM) ? 'CM' : 'IN');
      $this->rateReq['ItemList'][$cnt]['Weight']        = (float) abs($weight);
      $this->rateReq['ItemList'][$cnt]['WeightUOM']     = (!strcasecmp('KGS',$wtUOM) ? 'KGS' :  (!strcasecmp('OZ',$wtUOM) ? 'OZ' : 'LBS'));
      $this->rateReq['ItemList'][$cnt]['DeclaredValue'] = (float) abs($decVal);
      $this->rateReq['ItemList'][$cnt]['PackMethod']    = (!strcasecmp('S', $packMethod) ? 'S' : 'T');
      
   }
   
   /**
    * Enable On Demand Services for current Item
    * @param String - carrieer services codes
    * @access public
    */
   function addItemOnDemandServices($services)
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $current = $cnt - 1;
      $this->rateReq['ItemList'][$current]['OnDemandServices'] = array();
      $odservices = explode(",", $services);
      foreach($odservices AS $key=>$val){
         $this->rateReq['ItemList'][$current]['OnDemandServices'][$key] = trim($val);      
      }
   }
   
   /**
    * Add Supp Handling Fee Code for current Item
    * @param String - supp handling code
    * @access public
    */
   function addItemSuppHandlingCode($code)
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $current = $cnt - 1;
      $this->rateReq['ItemList'][$current]['SuppHandlingCode'] = $code;
   }
   
    /**
    * Add Supp Handling Fee Code for current Item
    * @param String - supp handling code
    * @access public
    */
   function addItemHandlingFee($amt)
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $current = $cnt - 1;
      $this->rateReq['ItemList'][$current]['SuppHandlingFee'] = $amt;
   }
   
   
   /**
    * Add Origin Code for current Item
    * @param String - origin code
    * @access public
    */
   function addItemOriginCode($code)
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $current = $cnt - 1;
      $this->rateReq['ItemList'][$current]['OriginCode'] = $code;
   }
   
   
   /**
    * Add Special Carrier Service for current Item
    * @param array - special carrier service
    * @access public
    */
   function addItemSpecialCarrierServices($services)
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $current = $cnt - 1;
      $this->rateReq['ItemList'][$current]['SpecialServices'] = array();
      $services = explode(",", $services);
      foreach($services AS $key=>$val){
         $this->rateReq['ItemList'][$current]['SpecialServices'][$key] = trim($val);      
      }
   }
   

   /**
    * Add Item with Fixed Rates
    * @param String - Reference Item Code
    * @param Float  - Quantity
    * @param String - Fee Type: F | 
    * @param Float  - Fee 1
    * @param Float  - Fee 2
    * @param String - Fixed Fee Code
    * @access public
    */
   function addItemFixed($refCode,$q,$t,$f1,$f2,$c)
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $this->rateReq['ItemList'][$cnt] = array();
      $this->rateReq['ItemList'][$cnt]['CalcMethod'] = 'F';
      $this->rateReq['ItemList'][$cnt]['RefCode']    = $refCode;
      $this->rateReq['ItemList'][$cnt]['Quantity']   = (float) abs($q);
      $this->rateReq['ItemList'][$cnt]['FeeType']    = (!strcasecmp('F',$t) ? 'F' : 'C');
      $this->rateReq['ItemList'][$cnt]['Fee1']       = (float) abs($f1);
      if (isset($f2)) $this->rateReq['ItemList'][$cnt]['Fee2']  = (float) abs($f2);
      $this->rateReq['ItemList'][$cnt]['FeeCode']    = $c;
   }
   
      /**
    * Add Item with Free Shipping
    * @param String - Reference Item Code
    * @param Float  - Quantity
    * @access public
    */
   function addItemFree($refCode,$q)
   {
      $cnt = (int) count($this->rateReq['ItemList']);
      $this->rateReq['ItemList'][$cnt] = array();
      $this->rateReq['ItemList'][$cnt]['CalcMethod'] = 'N';
      $this->rateReq['ItemList'][$cnt]['RefCode']    = $refCode;
      $this->rateReq['ItemList'][$cnt]['Quantity']   = (float) abs($q);
   }
     

   /**
    * Add Carrier
    * @param String - Carrier Code: UPS | USPS | DHL | FDX
    * @param String - Entry Point
    * @param String - Access Account Key (DHL)  
    * @param String - Access Account Postal Code (DHL): deprecated 6-13   
    * @param String - Account Country Code Key (DHL): added 5-16
    * @access public
    */
   function addCarrier($carrierCode, $entryPoint, $accessKey=false, $postalCode=false, $acctCountryCode=false)
   {
      $code = strtoupper($carrierCode);
      if (!preg_match("/^(UPS|USPS|DHL|FEDEX)\$/",$code)) return false;

      $this->rateReq['CarrierList'][$code] = array();
      $this->rateReq['CarrierList'][$code]['EntryPoint'] = $entryPoint;
      if ($code === 'DHL'){
         if ($accessKey)       $this->rateReq['CarrierList'][$code]['AccessKey']  = $accessKey;
         if ($acctCountryCode) $this->rateReq['CarrierList'][$code]['AcctCountryCode']  = $acctCountryCode;
         // if ($postalCode)   $this->rateReq['CarrierList'][$code]['PostalCode'] = strtoupper($postalCode);
      }
   }

   /**
    * Add Service
    * @param String - Carrier Code: UPS | USPS | DHL | FDX
    * @param String - Service Code (see docs for list)
    * @param Bool   - OnDemand Flag
    * @access public
    */
   function addService($carrierCode, $serviceCode, $onDemand=false)
   {
      $code = strtoupper($carrierCode);
      if (!preg_match("/^(UPS|USPS|DHL|FEDEX)\$/",$code)) return false;

      if (isset($this->rateReq['CarrierList'][$code])) {
         if (!isset($this->rateReq['CarrierList'][$code]['ServiceList'])) {
            $this->rateReq['CarrierList'][$code]['ServiceList'] = array();
         }
         $this->rateReq['CarrierList'][$code]['ServiceList'][] = array('Code' => strtoupper($serviceCode),
                                                                       'OnDemand' => $onDemand);
      }
   }
   
   /**
    * Set Max Service Weight
    * @param String - Carrier Service Code (see docs for list)
    * @param String - Max Weight Per Package Per Service
    * @access public
    */
   function setMaxServiceWeight($serviceCode, $weight)
   {
      $code = strtoupper($serviceCode);
      $this->maxWeights[$code] = (int)$weight;
   }
   
   /**
    * Set Max Service Length
    * @param String - Carrier Service Code (see docs for list)
    * @param String - Max Length Per Package Per Service
    * @access public
    */
   function setMaxServiceLength($serviceCode, $length)
   {
      $code = strtoupper($serviceCode);
      $this->maxLengths[$code] = (int)$length;
   }
   
   /**
    * Set Max Service Width
    * @param String - Carrier Service Code (see docs for list)
    * @param String - Max Length Per Package Per Service
    * @access public
    */
   function setMaxServiceWidth($serviceCode, $width)
   {
      $code = strtoupper($serviceCode);
      $this->maxWidths[$code] = (int)$width;
   }
   
   /**
    * Set Max Service Length
    * @param String - Carrier Service Code (see docs for list)
    * @param String - Max Length Per Package Per Service
    * @access public
    */
   function setMaxServiceHeight($serviceCode, $height)
   {
      $code = strtoupper($serviceCode);
      $this->maxHeights[$code] = (int)$height;
   }
   
   /**
    * Set Flat Rate Packaging
    * @param String - Carrier Service Code (see docs for list)
    * @param String - Flat Rate Flag ('PKG' / 'ENV' / 'ALL')
    * @access public
    */
   function setFlatRatePackaging($serviceCode, $flag)
   {
      $code = strtoupper($serviceCode);
      $flag = strtoupper($flag);
      if (!preg_match("/^(PKG|ENV|ALL)\$/",$flag)) return false;
      $this->flatRateFlag[$code] = $flag;
   }

   ### ----------------------------------------------------------------------
   ### Private Support Methods
   ### ----------------------------------------------------------------------

   function _makeGetItemShipRateXS_XML()
   {
      $env = $this->xml->createElement("Envelope");
      $this->_makeXML_header($env);

      $body = $this->xml->createElement("Body");
      $xs = $this->xml->createElement("GetItemShipRateXS");
      $xs->setAttribute("version", "2.3");
      $xs->appendChild($this->xml->createElement("Currency", $this->rateReq['Currency']));
      $xs->appendChild($this->xml->createElement("DetailLevel", $this->rateReq['DetailLevel']));    

      $this->_makeXML_destination($xs);
      $this->_makeXML_origin($xs);
      $this->_makeXML_itemList($xs);
      $this->_makeXML_carrierList($xs);

      $body->appendChild($xs);
      $env->appendChild($body);
      $this->xml->appendChild($env);
      return $this->xml->saveXML();
   }

   function _makeGetItemShipRateSS_XML()
   {
      $env = $this->xml->createElement("Envelope");
      $this->_makeXML_header($env);

      $body = $this->xml->createElement("Body");
      $ss = $this->xml->createElement("GetItemShipRateSS");
      $ss->setAttribute("version", "2.3");
      $ss->appendChild($this->xml->createElement("Currency", $this->rateReq['Currency']));
      $ss->appendChild($this->xml->createElement("DetailLevel", $this->rateReq['DetailLevel']));   

      $this->_makeXML_destination($ss);
      $this->_makeXML_itemList($ss);

      $body->appendChild($ss);
      $env->appendChild($body);
      $this->xml->appendChild($env);
      return $this->xml->saveXML();
   }

   function _makeXML_header($parent) {
      $head = $this->xml->createElement("Header");
      
      $head->appendChild($this->xml->createElement("AccountId", $this->accountId));
      
      if(!empty($this->refCode)) {
        $head->appendChild($this->xml->createElement("RefCode", $this->refCode));
      }
      
      $parent->appendChild($head);
   }
   
   function _makeXML_destination($parent)
   {
      $dstaddr = $this->xml->createElement("DestinationAddress");

      $dstaddr->appendChild($this->xml->createElement("ResidentialDelivery", ($this->rateReq['DestinationAddress']['Residential'] ? 'true' : 'false')));
      $dstaddr->appendChild($this->xml->createElement("CountryCode", $this->rateReq['DestinationAddress']['CountryCode']));
      $dstaddr->appendChild($this->xml->createElement("StateOrProvinceCode", $this->rateReq['DestinationAddress']['StateOrProvinceCode']));
      $dstaddr->appendChild($this->xml->createElement("PostalCode", $this->rateReq['DestinationAddress']['PostalCode']));

      $parent->appendChild($dstaddr);
   }

   function _makeXML_origin($parent)
   {
      $cnt = count($this->rateReq['OriginAddressList']);
      $addrlst = $this->xml->createElement("OriginAddressList");
      
      for ($i = 0; $i < $cnt; $i++) {
          $addr = $this->xml->createElement("OriginAddress");

          $addr->appendChild($this->xml->createElement("OriginCode", $this->rateReq['OriginAddressList'][$i]['OriginCode']));
          $addr->appendChild($this->xml->createElement("CountryCode", $this->rateReq['OriginAddressList'][$i]['CountryCode']));
          $addr->appendChild($this->xml->createElement("StateOrProvinceCode", $this->rateReq['OriginAddressList'][$i]['StateOrProvinceCode']));
          $addr->appendChild($this->xml->createElement("PostalCode", $this->rateReq['OriginAddressList'][$i]['PostalCode']));
          
          $addrlst->appendChild($addr);
      }
     
      $parent->appendChild($addrlst);
   }

   function _makeXML_itemList($parent)
   {
      $cnt = count($this->rateReq['ItemList']);
      $itmlst = $this->xml->createElement("ItemList");
      for ($i = 0; $i < $cnt; $i++) {
          $item = $this->xml->createElement("Item");

          $item->appendChild($this->xml->createElement("RefCode", $this->rateReq['ItemList'][$i]['RefCode']));
          $item->appendChild($this->xml->createElement("Quantity", $this->rateReq['ItemList'][$i]['Quantity']));
          $clcmthd = $this->xml->createElement("CalcMethod");
          $clcmthd->setAttribute("code", $this->rateReq['ItemList'][$i]['CalcMethod']);

          if ($this->rateReq['ItemList'][$i]['CalcMethod'] === 'C' || $this->rateReq['ItemList'][$i]['CalcMethod'] === 'CI') {
            $carrier = $this->xml->createElement("CarrierCalcProps");

            $carrier->appendChild($this->xml->createElement("Weight", $this->rateReq['ItemList'][$i]['Weight']));
            $carrier->appendChild($this->xml->createElement("WeightUOM", $this->rateReq['ItemList'][$i]['WeightUOM']));
            $carrier->appendChild($this->xml->createElement("Length", $this->rateReq['ItemList'][$i]['Length']));
            $carrier->appendChild($this->xml->createElement("Width", $this->rateReq['ItemList'][$i]['Width']));
            $carrier->appendChild($this->xml->createElement("Height", $this->rateReq['ItemList'][$i]['Height']));
            $carrier->appendChild($this->xml->createElement("DimUOM", $this->rateReq['ItemList'][$i]['DimUOM']));
            $carrier->appendChild($this->xml->createElement("DeclaredValue", $this->rateReq['ItemList'][$i]['DeclaredValue']));
            $carrier->appendChild($this->xml->createElement("PackMethod", $this->rateReq['ItemList'][$i]['PackMethod']));

            if(isset($this->rateReq['ItemList'][$i]['LotSize'])) {
              $carrier->appendChild($this->xml->createElement("LotSize", $this->rateReq['ItemList'][$i]['LotSize']));
            }
            if(isset($this->rateReq['ItemList'][$i]['OriginCode'])) {
              $carrier->appendChild($this->xml->createElement("OriginCode", $this->rateReq['ItemList'][$i]['OriginCode']));
            }
            if(isset($this->rateReq['ItemList'][$i]['SuppHandlingCode'])) {
              $carrier->appendChild($this->xml->createElement("SuppHandlingCode", $this->rateReq['ItemList'][$i]['SuppHandlingCode']));
            }
            if(isset($this->rateReq['ItemList'][$i]['SuppHandlingFee'])) {
              $carrier->appendChild($this->xml->createElement("SuppHandlingFee", $this->rateReq['ItemList'][$i]['SuppHandlingFee']));
            }
            if(isset($this->rateReq['ItemList'][$i]['OnDemandServices'])){   
              $cnt_od  = count($this->rateReq['ItemList'][$i]['OnDemandServices']); 
              if ($cnt_od > 0){
                 $ondemand = $this->xml->createElement("OnDemandServices");
                 for ($j = 0; $j < $cnt_od; $j++) {
                    $ondemand->appendChild($this->xml->createElement("ODService", $this->rateReq['ItemList'][$i]['OnDemandServices'][$j]));
                 } 
                 $carrier->appendChild($ondemand);  
              } 
            }
            if(isset($this->rateReq['ItemList'][$i]['SpecialServices'])) {
              $cnt_ss  = count($this->rateReq['ItemList'][$i]['SpecialServices']); 
              if ($cnt_ss > 0){
                 $special = $this->xml->createElement("SpecialServices");
                 for ($j = 0; $j < $cnt_ss; $j++) {
                    $special->appendChild($this->xml->createElement($this->rateReq['ItemList'][$i]['SpecialServices'][$j], "TRUE"));     
                 } 
                 $carrier->appendChild($special);    
              } 
            } 

            $clcmthd->appendChild($carrier);
          } else if ($this->rateReq['ItemList'][$i]['CalcMethod'] === 'F') {
            $fixed = $this->xml->createElement("FixedCalcProps");

            $fixed->appendChild($this->xml->createElement("FeeType", $this->rateReq['ItemList'][$i]['FeeType']));
            if(isset($this->rateReq['ItemList'][$i]['Fee1'])) {
              $fixed->appendChild($this->xml->createElement("Fee1", $this->rateReq['ItemList'][$i]['Fee1']));
            }
            if(isset($this->rateReq['ItemList'][$i]['Fee2'])) {
              $fixed->appendChild($this->xml->createElement("Fee2", $this->rateReq['ItemList'][$i]['Fee2']));
            }
            if(isset($this->rateReq['ItemList'][$i]['FeeCode'])) {
              $fixed->appendChild($this->xml->createElement("FeeCode", $this->rateReq['ItemList'][$i]['FeeCode']));
            }
            if(isset($this->rateReq['ItemList'][$i]['OriginCode'])) {
              $fixed->appendChild($this->xml->createElement("OriginCode", $this->rateReq['ItemList'][$i]['OriginCode']));
            }

            $clcmthd->appendChild($fixed);
          } else if ($this->rateReq['ItemList'][$i]['CalcMethod'] === 'N') {
                // nothing to add
          }

          $item->appendChild($clcmthd);
          $itmlst->appendChild($item);
      }   
      $parent->appendChild($itmlst);
   }

   function _makeXML_carrierList($parent)
   {
      $cnt  = count($this->rateReq['CarrierList']);
      $carrierlst = $this->xml->createElement("CarrierList");
      foreach ($this->rateReq['CarrierList'] AS $key => $val) {
         // Make certain that we have both carrier and service codes defined
         if (isset($val['ServiceList']) && sizeof($val['ServiceList']) > 0) {
            $carrier = $this->xml->createElement("Carrier");

            $carrier->setAttribute("code", $key);
            $carrier->appendChild($this->xml->createElement("EntryPoint", $val['EntryPoint']));
            if ($key === 'DHL') {
              if (isset($val['AccessKey'])) { 
                $carrier->appendChild($this->xml->createElement("AccessKey", $val['AccessKey']));
              }
              if (isset($val['AcctCountryCode'])) {
                $carrier->appendChild($this->xml->createElement("AcctCountryCode", $val['AcctCountryCode']));
              }
            }
            if (array_key_exists($key, $this->flatRateFlag)) {
              $carrier->appendChild($this->xml->createElement("FlatRatePackaging", $this->flatRateFlag[$key]));
            }
            $svclst = $this->xml->createElement("ServiceList");
            for ($i = 0, $c = count($val['ServiceList']); $i < $c; $i++) {
              $svc = $this->xml->createElement("Service");
              $svc->setAttribute("code", $val['ServiceList'][$i]['Code']);              
              if ($val['ServiceList'][$i]['OnDemand']  || array_key_exists($val['ServiceList'][$i]['Code'], $this->maxWeights) || array_key_exists($val['ServiceList'][$i]['Code'], $this->maxLengths)) {
                  if (array_key_exists($val['ServiceList'][$i]['Code'], $this->maxWeights)){ 
                    $svc->appendChild($this->xml->createElement("PkgMaxWeight", $this->maxWeights[$val['ServiceList'][$i]['Code']]));
                  }                
                  if (array_key_exists($val['ServiceList'][$i]['Code'], $this->maxLengths)){ 
                    $svc->appendChild($this->xml->createElement("PkgMaxLength", $this->maxLengths[$val['ServiceList'][$i]['Code']]));
                    $svc->appendChild($this->xml->createElement("PkgMaxWidth", $this->maxWidths[$val['ServiceList'][$i]['Code']]));
                    $svc->appendChild($this->xml->createElement("PkgMaxHeight", $this->maxHeights[$val['ServiceList'][$i]['Code']]));                     
                  }                                              
                  if ($val['ServiceList'][$i]['OnDemand']) {
                    $svc->appendChild($this->xml->createElement("OnDemand", "true"));
                  }
              }
              $svclst->appendChild($svc);
            }
            $carrier->appendChild($svclst);
            $carrierlst->appendChild($carrier);
         }
      }
      $parent->appendChild($carrierlst);
   }
   
   /**
   * Creates an array that mimics thats generated by the API results
   * @param    int      error #
   * @param    string   error message
   * @param    string   Severity (CRITICAL, WARNING, NOTICE)
   * @access   private
   **/
   function _createError($errorCode, $errorMsg, $severity='CRITICAL') {
      $error = array('ErrorList' => array());
      $error['ErrorList'][] = array(
            'Code' => $errorCode,
            'Message' => $errorMsg,
            'Severity' => $severity
            );
      return $error;
   }

  function dom_to_array($root) 
  {
    $result = array(); 

    if ($root->hasAttributes()) 
    { 
        $attrs = $root->attributes; 

        foreach ($attrs as $i => $attr) 
            $result[$attr->name] = $attr->value; 
    } 

    if ($root->hasChildNodes()) {
      $children = $root->childNodes; 

      if ($children->length == 1) 
      { 
          $child = $children->item(0); 

          if ($child->nodeType == XML_TEXT_NODE) 
          { 
             $result['_value'] = $child->nodeValue;

              if (count($result) == 1) {
                  return $result['_value']; 
              }
              else 
                  return $result;
          } 
      } 

      $group = array(); 

      for($i = 0; $i < $children->length; $i++) 
      { 
          $child = $children->item($i);

          if($child->nodeType != XML_TEXT_NODE) { 

            if (!isset($result[$child->nodeName]))
                $result[$child->nodeName] = $this->dom_to_array($child); 
            else 
            { 
                if (!isset($group[$child->nodeName])) 
                { 
                    $tmp = $result[$child->nodeName]; 
                    $result[$child->nodeName] = array($tmp); 
                    $group[$child->nodeName] = 1; 
                } 
                $result[$child->nodeName][] = $this->dom_to_array($child); 
            } 
          }
      } 
  }
  return $result; 
} 

   
}

?>