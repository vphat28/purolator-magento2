<?php

namespace Purolator\Shipping\Model\Purolator;

class ShipmentService
{
    const SHIPMENT_REQUEST_TYPE = 'ShippingService';

    const SERVICE_AVAILABILITY_SERVICE = 'ServiceAvailabilityService';

    /**
     * @var \Purolator\Shipping\Helper\Data
     */
    protected $helper;

    protected $shipment;

    protected $request;

    /**
     * @var Request
     */
    protected $purolatorRequest;

    /**
     * @var \stdClass
     */
    protected $requestData;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $httpRequest;

    /**
     * Shipment constructor.
     * @param \Purolator\Shipping\Helper\Data $helper
     * @param Request $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Purolator\Shipping\Helper\Data $helper,
        Request $request,
        \Magento\Directory\Model\Region $region,
        \Magento\Framework\App\RequestInterface $httpRequest
    )
    {
        $this->helper = $helper;
        $this->purolatorRequest = $request;
        $this->httpRequest = $httpRequest;
        $this->requestData = new \stdClass();
    }

    /**
     * @param $shipment
     */
    public function setShipment($shipment)
    {
        $this->shipment = $shipment;
    }

    /**
     * @return mixed
     */
    public function getShipment()
    {
        return $this->shipment;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function createShipmentFromRequest($request)
    {
        $this->setShipment($request->getOrderShipment());
        $this->setRequest($request);
        $this->getOptionsData();

        $purolatorRequest = $this->createShipmentRequest();

        $client = $this->purolatorRequest->createPWSSOAPClient(self::SHIPMENT_REQUEST_TYPE);
        $response = $client->ValidateShipment($purolatorRequest);
        $this->helper->checkResponse($response);
        $response = $client->CreateShipment($purolatorRequest);
        $this->helper->checkResponse($response);

        return [
            'response' => $response,
            'purolator_request' => $purolatorRequest,
        ];
    }

    protected function getOptionsData()
    {
        $shipment = $this->httpRequest->getParam('shipment');

        if(empty($shipment['purolator'])) {
            return ;
        }

        foreach ($shipment['purolator'] as $name => $value) {
            if (!empty($value)) {
                $this->getRequest()->setData($name, $value);
            }
        }
    }

    /**
     * @return \stdClass
     */
    protected function createShipmentRequest()
    {
        $this->setSenderInformation();
        $this->setReceiverInformation();
        $this->setPackageInformation();
        $this->setPaymentInformation();
        $this->setPickupInformation();
        $this->setTrackingReferenceInformation();
        $this->setShipmentDocumentType();
        $this->setInternationalInformation();
        $this->setOptionsInformation();

        return $this->requestData;
    }

    protected function setPackageInformation()
    {
        //Package Information
        $this->requestData->Shipment->PackageInformation = new \stdClass();
        $this->requestData->Shipment->PackageInformation->ServiceID = $this->getRequest()->getShippingMethod();

        //QTY calculations
        $weight = 0;
        if (!empty($this->getRequest()->getPackageItems())) {
            foreach ($this->getRequest()->getPackageItems() as $item) {
                $currentWeight = (!empty($item['weight'])) ? $item['weight'] : $this->helper->getConfig('default_weight');
                $weight += $currentWeight * $item['qty'];
            }
        } else {
            foreach ($this->getShipment()->getAllItems() as $item) {
                $currentWeight = (!empty($item->getWeight())) ? $item->getWeight() : $this->helper->getConfig('default_weight');
                $weight += $currentWeight * $item->getQty();
            }
        }

        $weightUnits['POUND'] = 'lb';
        $weightUnits['KILOGRAM'] = 'kg';

        if (!empty($this->getRequest()->getPackageParams()) && !empty($this->getRequest()->getPackageParams()->getWeight())) {
            $weight = $this->getRequest()->getPackageParams()->getWeight();
        }

        if (floor($weight) < 1) {
            $weight = 1;
        }

        if (!empty($this->getRequest()->getPackageParams()) && !empty($this->getRequest()->getPackageParams()->getWeightUnits())) {
            $weightUnit = $weightUnits[$this->getRequest()->getPackageParams()->getWeightUnits()];
        } else {
            $options = $this->helper->getStoreLocaleOptions();
            $weightUnit = substr($options['weight_unit'], 0 , 2);
        }

        $this->requestData->Shipment->PackageInformation->TotalWeight = new \stdClass();
        $this->requestData->Shipment->PackageInformation->TotalWeight->Value = $weight;
        $this->requestData->Shipment->PackageInformation->TotalWeight->WeightUnit = $weightUnit;

        $this->requestData->Shipment->PackageInformation->PiecesInformation = new \stdClass();

        $dimension_units['INCH'] = 'in';
        $dimension_units['CENTIMETER'] = 'cm';

        //Just one box
        if (!empty($this->getRequest()->getPackageParams())) {
            $this->requestData->Shipment->PackageInformation->TotalPieces = 1;
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Weight = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Weight->Value = $this->getRequest()->getPackageParams()->getWeight();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Weight->WeightUnit = $weightUnits[$this->getRequest()->getPackageParams()->getWeightUnits()];
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Length = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Length->Value = $this->getRequest()->getPackageParams()->getLength();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Length->DimensionUnit = $dimension_units[$this->getRequest()->getPackageParams()->getDimensionUnits()];
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Height = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Height->Value = $this->getRequest()->getPackageParams()->getHeight();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Height->DimensionUnit = $dimension_units[$this->getRequest()->getPackageParams()->getDimensionUnits()];
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Width = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Width->Value = $this->getRequest()->getPackageParams()->getWidth();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Width->DimensionUnit = $dimension_units[$this->getRequest()->getPackageParams()->getDimensionUnits()];

            return ;
        }

        $box_sizes = explode(",", $this->helper->getConfig('box_size'));
        $ratio = (float)$this->helper->getConfig('ratio');
        foreach ($box_sizes as $key => $box_size) {
            $arr = explode("*", $box_size);
            if (count($arr) != 3) {
                unset($box_sizes[$key]);
                continue;
            }

            $temp = [];
            $temp["width"] = $arr[0];
            $temp["height"] = $arr[1];
            $temp["length"] = $arr[2];
            $temp["volume"] = $arr[0] * $arr[1] * $arr[2];
            $temp["useable_volume"] = (float)($temp["volume"] / 100) * $ratio;
            unset($box_sizes[$key]);
            if (!empty($temp)) {
                $box_sizes[$key] = $temp;
            }
        }

        $box_sizes = $this->sortByVolume($box_sizes);
        //Calculate the volume of the ordered items
        $total_item_volume = 0;
        $item_volumes = [];
        foreach ($this->getShipment()->getAllItems() as $item) {

            $item_width = $this->helper->getConfig('width');
            $item_height = $this->helper->getConfig('height');
            $item_length = $this->helper->getConfig('length');

            if (!empty($item['item_width'])) {
                $item_width = $item['item_width'];
            }
            if (!empty($item['item_height'])) {
                $item_height = $item['item_height'];
            }
            if (!empty($item['item_length'])) {
                $item_length = $item['item_length'];
            }

            $item_volume = $item_length * $item_width * $item_height;
            $dimentions["width"] = $item_width;
            $dimentions["length"] = $item_length;
            $dimentions["height"] = $item_height;
            $item_volumes[$item['product_id']] = [
                "volume" => $item_volume,
                "qty" => $item['qty'],
                "dim" => $dimentions,
                "weight" => (!empty($item['weight'])) ? $item['weight'] : $this->helper->getConfig('default_weight'),
            ];
            $total_item_volume += $item_volume * $item['qty'];
        }

        if (!empty($box_sizes[0]["useable_volume"]) && $total_item_volume < $box_sizes[0]["useable_volume"]) {

            $this->requestData->Shipment->PackageInformation->TotalPieces = 1;
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Weight = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Weight->Value = $weight;
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Weight->WeightUnit = $weightUnit;
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Length = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Length->Value = $box_sizes[0]["length"];
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Length->DimensionUnit = $this->helper->getConfig('measure_units_length');
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Height = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Height->Value = $box_sizes[0]["height"];
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Height->DimensionUnit = $this->helper->getConfig('measure_units_length');
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Width = new \stdClass();
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Width->Value = $box_sizes[0]["width"];
            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Width->DimensionUnit = $this->helper->getConfig('measure_units_length');

        } else {

            $result = $this->getBoxes($item_volumes, $box_sizes[0]);

            $this->requestData->Shipment->PackageInformation->TotalPieces = count($result["Pieces"]);

            $width = $box_sizes[0]["width"];
            $height = $box_sizes[0]["height"];
            $length = $box_sizes[0]["length"];

            for ($i = 0; $i < count($result["Pieces"]); $i++) {
                if (array_key_exists("id", $result["Pieces"][$i])) {
                    //Custom Package
                    if (isset($result["Pieces"][$i]["weight"])) {
                        $weight = 1;
                        if ($result["Pieces"][$i]["weight"] > 1) {
                            $weight = $result["Pieces"][$i]["weight"];
                        }
                    }

                    $width = $result["Pieces"][$i]["dim"]["width"];
                    $height = $result["Pieces"][$i]["dim"]["height"];
                    $length = $result["Pieces"][$i]["dim"]["length"];

                } else {
                    //Normal box
                    if (isset($result["Pieces"][$i]["weight"])) {
                        $weight = 1;
                        if ($result["Pieces"][$i]["weight"] > 1) {
                            $weight = $result["Pieces"][$i]["weight"];
                        }
                    }
                }


                //$weight =  $this->helper->getConvertedWeight($weight, $this->helper->getConfig('measure_units_weight'), 'lb');
                if ($weight < 1) {
                    $weight = 1;
                }
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i] = new \stdClass();
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Weight = new \stdClass();
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Length = new \stdClass();
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Height = new \stdClass();
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Width = new \stdClass();


                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Weight->Value = $weight;
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Weight->WeightUnit = $weightUnit;

                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Length->Value = $length;
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Length->DimensionUnit = $this->helper->getConfig('measure_units_length');

                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Height->Value = $height;
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Height->DimensionUnit = $this->helper->getConfig('measure_units_length');

                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Width->Value = $width;
                $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Width->DimensionUnit = $this->helper->getConfig('measure_units_length');
            }
        }
    }

    protected function setSenderInformation()
    {
        $storeInformation = $this->helper->getStoreInformation();

        $this->requestData->Shipment = new \stdClass();
        $this->requestData->Shipment->SenderInformation = new \stdClass();
        $this->requestData->Shipment->SenderInformation->Address = new \stdClass();
        $this->requestData->Shipment->SenderInformation->TaxNumber = $storeInformation['merchant_vat_number'];
        $this->requestData->Shipment->SenderInformation->Address->Name = substr($this->getRequest()->getShipperContactPersonName(), 0, 30);
        $this->requestData->Shipment->SenderInformation->Address->Company = substr($this->getRequest()->getShipperContactCompanyName(), 30, 30);
        $this->requestData->Shipment->SenderInformation->Address->StreetNumber = substr($this->getRequest()->getData('shipper_address_street_1'), 0, 6);
        $this->requestData->Shipment->SenderInformation->Address->StreetName = substr($this->getRequest()->getShipperAddressStreet(), 0, 30);
        $this->requestData->Shipment->SenderInformation->Address->StreetAddress2 = substr($this->getRequest()->getData('shipper_address_street_2'), 30, 25);

        $this->requestData->Shipment->SenderInformation->Address->City = $this->helper->transliterateString($this->getRequest()->getShipperAddressCity());
        $this->requestData->Shipment->SenderInformation->Address->Province = $this->helper->transliterateString($this->getRequest()->getShipperAddressStateOrProvinceCode());
        $this->requestData->Shipment->SenderInformation->Address->Country = $this->helper->transliterateString($this->getRequest()->getShipperAddressCountryCode());
        $this->requestData->Shipment->SenderInformation->Address->PostalCode = $this->getRequest()->getShipperAddressPostalCode();
        $this->requestData->Shipment->SenderInformation->Address->PhoneNumber = new \stdClass();

        $this->requestData->Shipment->SenderInformation->Address->PhoneNumber->CountryCode = $this->helper->getTelCodeByCountry($this->getRequest()->getShipperAddressCountryCode());
        $this->requestData->Shipment->SenderInformation->Address->PhoneNumber->AreaCode = $this->helper->getAreaCode($this->getRequest()->getShipperContactPhoneNumber());
        $this->requestData->Shipment->SenderInformation->Address->PhoneNumber->Phone = $this->helper->getTel($this->getRequest()->getShipperContactPhoneNumber());
    }

    protected function setReceiverInformation()
    {
        $this->requestData->Shipment->ReceiverInformation = new \stdClass();
        $this->requestData->Shipment->ReceiverInformation->TaxNumber = (!empty($this->getShipment()->getOrder()->getCustomerTaxvat())) ? $this->getShipment()->getOrder()->getCustomerTaxvat() : '';

        $this->requestData->Shipment->ReceiverInformation->Address = new \stdClass();
        $this->requestData->Shipment->ReceiverInformation->Address->Name = substr($this->getRequest()->getRecipientContactPersonName(), 0, 30);
        $this->requestData->Shipment->ReceiverInformation->Address->Company = substr($this->getRequest()->getRecipientContactCompanyName(), 30, 30);
        $this->requestData->Shipment->ReceiverInformation->Address->StreetName = substr($this->getRequest()->getRecipientAddressStreet(), 0, 35);
        $this->requestData->Shipment->ReceiverInformation->Address->StreetAddress2 = substr($this->helper->transliterateString($this->getRequest()->getData('recipient_address_street_1') . " " . $this->getRequest()->getData('recipient_address_street_2')), 35, 25);
        $this->requestData->Shipment->ReceiverInformation->Address->StreetAddress3 = substr($this->helper->transliterateString($this->getRequest()->getData('recipient_address_street_1') . " " . $this->getRequest()->getData('recipient_address_street_2')), 60, 25);
        $this->requestData->Shipment->ReceiverInformation->Address->City = $this->helper->transliterateString($this->getRequest()->getRecipientAddressCity());
        $this->requestData->Shipment->ReceiverInformation->Address->Province = $this->helper->transliterateString($this->getRequest()->getRecipientAddressStateOrProvinceCode());
        $this->requestData->Shipment->ReceiverInformation->Address->Country = $this->helper->transliterateString($this->getRequest()->getRecipientAddressCountryCode());
        $this->requestData->Shipment->ReceiverInformation->Address->PostalCode = str_replace(' ', '', $this->getRequest()->getRecipientAddressPostalCode());
        $this->requestData->Shipment->ReceiverInformation->Address->PhoneNumber = new \stdClass();

        $this->requestData->Shipment->ReceiverInformation->Address->PhoneNumber->CountryCode = $this->helper->getTelCodeByCountry($this->getRequest()->getRecipientAddressCountryCode());
        $this->requestData->Shipment->ReceiverInformation->Address->PhoneNumber->AreaCode = $this->helper->getAreaCode($this->getRequest()->getRecipientContactPhoneNumber());
        $this->requestData->Shipment->ReceiverInformation->Address->PhoneNumber->Phone = $this->helper->getTel($this->getRequest()->getRecipientContactPhoneNumber());
    }

    protected function setPaymentInformation()
    {
        $this->requestData->Shipment->PaymentInformation = new \stdClass();
        $this->requestData->Shipment->PaymentInformation->PaymentType = "Sender";
        $this->requestData->Shipment->PaymentInformation->BillingAccountNumber = $this->helper->getAPIAccountNumber();
        $this->requestData->Shipment->PaymentInformation->RegisteredAccountNumber = $this->helper->getRegisteredAccount();


    }

    protected function setPickupInformation()
    {
        $this->requestData->Shipment->PickupInformation = new \stdClass();
        $this->requestData->Shipment->PickupInformation->PickupType = "DropOff";


    }

    protected function setTrackingReferenceInformation()
    {
        $this->requestData->Shipment->TrackingReferenceInformation = new \stdClass();
        $this->requestData->Shipment->TrackingReferenceInformation->Reference1 = $this->getShipment()->getOrder()->getIncrementId();


    }

    protected function setShipmentDocumentType()
    {
        $this->requestData->PrinterType = $this->helper->getConfig("printertype");
    }

    protected function setInternationalInformation()
    {
        if ($this->requestData->Shipment->SenderInformation->Address->Country == $this->requestData->Shipment->ReceiverInformation->Address->Country) {
            return ;
        }

        $this->requestData->Shipment->InternationalInformation = new \stdClass();
        $this->requestData->Shipment->InternationalInformation->DocumentsOnlyIndicator = false;

        //Duty information
        $this->requestData->Shipment->InternationalInformation->DutyInformation = new \stdClass();
        $this->requestData->Shipment->InternationalInformation->DutyInformation->BillDutiesToParty = $this->helper->getConfig("billdutiestoparty");
        $this->requestData->Shipment->InternationalInformation->DutyInformation->BusinessRelationship = $this->helper->getConfig("businessrel");
        $this->requestData->Shipment->InternationalInformation->DutyInformation->Currency = $this->helper->getConfig("dutyccy");

        $this->requestData->Shipment->InternationalInformation->ImportExportType = $this->helper->getConfig("ietype");
        $this->requestData->Shipment->InternationalInformation->CustomsInvoiceDocumentIndicator = ($this->helper->getConfig("customerinvoice")) ? true : false;

        //Content details
        $this->requestData->Shipment->InternationalInformation->ContentDetails = new \stdClass();
        $i = 0;

        foreach ($this->getShipment()->getAllItems() as $item) {

            $qty = $item->getQty();

            $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i] = new \stdClass();

            $harmcode = $item->getOrderItem()->getHarmonizedcodeAttribute();
            if (trim($harmcode) == "") {
                $harmcode = $this->helper->getConfig("harmcode");
            }
            $com = $item->getOrderItem()->getCountryOfManufacture();
            if (trim($com) == "") {
                $com = $this->helper->getConfig("com");
            }

            $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->Description = $this->helper->transliterateString(substr($item->getName(), 0, 49));
            $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->HarmonizedCode = $harmcode;
            $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->CountryOfManufacture = $com;
            $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->ProductCode = $item->getProductId();
            $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->UnitValue = $item->getPrice();

            $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->Quantity = $qty;


            if (strtolower($this->requestData->Shipment->ReceiverInformation->Address->Country) == "us") {
                if (!empty($this->helper->getConfig("textilei"))) {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->TextileIndicator = true;
                } else {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->TextileIndicator = (empty($item->getOrderItem()->getTextileindicatorAttribute())) ? false : true;
                }

                if (empty($item->getOrderItem()->getTextilemanAttribute())) {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->TextileManufacturer = (empty($item->getOrderItem()->getTextilemanAttribute())) ? false : true;
                } else {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->TextileManufacturer = (empty($this->helper->getConfig("textilem"))) ? false : true;
                }
                if (!empty($this->helper->getConfig("nafta"))) {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->NAFTADocumentIndicator = true;
                } else {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->NAFTADocumentIndicator = (empty($item->getOrderItem()->getNaftaAttribute())) ? false : true;
                }

                if (!empty($this->helper->getConfig("fcc"))) {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->FCCDocumentIndicator = true;
                } else {
                    $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->FCCDocumentIndicator = (empty($item->getOrderItem()->getFccAttribute())) ? false : true;
                }
            }

            if (!empty($this->helper->getConfig("sip"))) {
                $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->SenderIsProducerIndicator = true;
            } else {
                $this->requestData->Shipment->InternationalInformation->ContentDetails->ContentDetail[$i]->SenderIsProducerIndicator = (empty($item->getOrderItem()->getSipAttribute())) ? false : true;
            }

            $i++;
        }

        // if ($same_as_billing == 0) {
        $this->requestData->Shipment->InternationalInformation->BuyerInformation = new \stdClass();
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address = new \stdClass();
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->Name = substr($this->getRequest()->getRecipientContactPersonFirstName() . " " . $this->getRequest()->getRecipientContactPersonLastName(), 0, 30);
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->Company = substr($this->getRequest()->getRecipientContactPersonFirstName() . " " . $this->getRequest()->getRecipientContactPersonLastName(), 30, 30);
        $street = $this->getShipment()->getBillingAddress()->getStreet();

        if (!isset($street[0])) {
            $street[0] = "";
        }

        if (!isset($street[1])) {
            $street[1] = "";
        }

        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->StreetNumber = substr($this->helper->transliterateString($street[0] . " " . $street[1]), 0, 6);
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->StreetName = substr($this->helper->transliterateString($street[0] . " " . $street[1]), 6, 35);
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->StreetAddress2 = substr($this->helper->transliterateString($street[0] . " " . $street[1]), 41, 25);
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->StreetAddress3 = substr($this->helper->transliterateString($street[0] . " " . $street[1]), 66, 25);
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->City = $this->helper->transliterateString($this->getRequest()->getRecipientAddressCity());
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->Province = $this->helper->transliterateString($this->getRequest()->getRecipientAddressRegionCode());
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->Country = $this->helper->transliterateString($this->getRequest()->getRecipientAddressCountryCode());
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->PostalCode = $this->getRequest()->getRecipientAddressPostalCode();

        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->PhoneNumber = new \stdClass();
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->PhoneNumber->CountryCode = $this->helper->getTelCodeByCountry($this->getRequest()->getRecipientAddressCountryCode());
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->PhoneNumber->AreaCode = $this->helper->getAreaCode($this->getRequest()->getRecipientContactPhoneNumber());
        $this->requestData->Shipment->InternationalInformation->BuyerInformation->Address->PhoneNumber->Phone = $this->helper->getTel($this->getRequest()->getRecipientContactPhoneNumber());
    }

    protected function setOptionsInformation()
    {
        //Define OptionsInformation
        $this->requestData->Shipment->PackageInformation->OptionsInformation = new \stdClass();
        $this->requestData->Shipment->PackageInformation->OptionsInformation->Options = new \stdClass();
        $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair = new \stdClass();


        $service_options = $this->getServiceOptions(null, $this->requestData);

        $i = 0;
        $this->requestData->Shipment->PackageInformation->OptionsInformation = new \stdClass();
        $this->requestData->Shipment->PackageInformation->OptionsInformation->Options = new \stdClass();

        foreach ($service_options->Services->Service as $service) {
            if ($service->ID != $this->requestData->Shipment->PackageInformation->ServiceID) {
                continue;
            }

            foreach ($service->Options->Option as $option) {

                if (!empty($option->ID) && $option->ID == 'SpecialHandling' && !empty($this->getRequest()->getPackagingType())) {
                    $totalPieces = $this->requestData->Shipment->PackageInformation->TotalPieces;
                    $optionIdValuePair = (object) [
                        (object) [
                            'ID' => 'SpecialHandling',
                            'Value' => 'true',
                        ],
                        (object) [
                            'ID' => 'SpecialHandlingType',
                            'Value' => $this->getRequest()->getPackagingType(),
                        ],
                    ];
                    if ($totalPieces == 1) {
                        $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece->Options =
                            $optionIdValuePair;
                    } else {
                        for($c = 0; $c < $totalPieces; $c++ ) {
                            $this->requestData->Shipment->PackageInformation->PiecesInformation->Piece[$c]->Options =
                                $optionIdValuePair;
                        }
                    }
                }

                if (!empty($option->ID) && !empty($this->getRequest()->getData($option->ID))) {
                    if ($option->ID == 'DangerousGoods' && !empty($this->getRequest()->getData('DangerousGoods'))) {

                        // skip dangerous
                        continue;

                        $this->requestData->Shipment->PackageInformation->DangerousGoodsDeclarationDocumentIndicator = true;

                        $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i] = new \stdClass();
                        $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->ID = 'DangerousGoods';
                        $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->Value = 'true';
                        $i++;

                        if (!empty($this->getRequest()->getData('DangerousGoodsClass'))) {
                            $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i] = new \stdClass();
                            $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->ID = 'DangerousGoodsClass';
                            $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->Value = $this->getRequest()->getData('DangerousGoodsClass');
                            $i++;
                        }

                        if (!empty($this->getRequest()->getData('DangerousGoodsMode'))) {
                            $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i] = new \stdClass();
                            $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->ID = 'DangerousGoodsMode';
                            $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->Value = $this->getRequest()->getData('DangerousGoodsMode');
                            $i++;
                        }

                        continue;
                    }


                    $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i] = new \stdClass();
                    $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->ID = $option->ID;
                    $this->requestData->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair[$i]->Value = $this->getRequest()->getData($option->ID);
                    $i++;
                }
            }
        }
    }


    /**
     * Get boxes
     *
     * @param array $item_volumes
     * @param array $box
     *
     * @return array
     */
    private function getBoxes($item_volumes, $box)
    {
        $result = [];
        $result["items"] = [];
        $temp = [];
        $result["Pieces"] = [];
        foreach ($item_volumes as $key => $vol) {

            for ($i = 0; $i < (int)$vol["qty"]; $i++) {
                array_push($temp, ["vol" => $vol["volume"], "id" => $key, "qty" => 1, "weight" => $vol['weight']]); //$vol["qty"]));
            }
            if ($vol["volume"] > $box["useable_volume"]) {
                $result["items"][$key] = "nobox";
                for ($i = 0; $i < $vol["qty"]; $i++) {
                    array_push($result["Pieces"], ["id" => $key, "dim" => $vol["dim"], "weight" => $vol['weight']]);
                }
            } else {
                $result["items"][$key] = "hasbox";
            }
        }
        $volume = 0;
        $box_count = 0;
        $box_volume = $box["useable_volume"];
        $weight = 0;
        foreach ($temp as $key => $row) {
            $weight += $row['weight'];
            if ($row["vol"] > $box_volume) {
                continue;
            }

            if ($volume < $box_volume) {
                $volume += $row["vol"];

                if (isset($temp[$key + 1]) && ($volume + $temp[$key + 1]["vol"] > $box_volume)) {
                    array_push($result["Pieces"], [true, "weight" => $weight]);
                    $box_count++;
                    $volume = 0;
                    $weight = 0;
                    continue;
                }

                if (!isset($temp[$key + 1])) {
                    array_push($result["Pieces"], [true, "weight" => $weight]);
                    $box_count++;
                    $volume = 0;
                    $weight = 0;
                    continue;
                }
            }
        }

        $result["boxes"] = $box_count;
        return $result;
    }

    /**
     * Get Package Weight LB
     *
     * @param type $_order Order
     *
     * @return type
     */
    private function getPackageWeightLb()
    {
        $weight = 0;
        foreach ($this->getShipment()->getAllItems() as $item) {
            $qty = ($item->getQty() * 1);
            $currentWeight = (!empty($item->getWeightUnits())) ? $item->getWeightUnits() : $this->helper->getConfig('default_weight');
            $currentUnit = (!empty($item->getWeightUnits())) ? $item->getWeightUnits() : $this->helper->getConfig('measure_units_weight');
            $weight += $this->helper->getConvertedWeightLb(($qty * $currentWeight), $currentUnit);
        }

        return $weight;
    }

    /**
     * @param $boxes
     * @return mixed
     */
    private function sortByVolume($boxes)
    {
        $temp_loc = null;
        for ($i = 0; $i < count($boxes); $i++) {
            for ($j = 0; $j < $i; $j++) {
                if ($boxes[$i] < $boxes[$j]) {
                    $temp_loc = $boxes[$i];
                    $boxes[$i] = $boxes[$j];
                    $boxes[$j] = $temp_loc;
                }
            }
        }

        return $boxes;
    }

    protected function getServiceOptions()
    {
        $client = $this->purolatorRequest->createPWSSOAPClient(self::SERVICE_AVAILABILITY_SERVICE);
        //Define the request object
        $request = new \stdClass();
        $request->SenderAddress = new \stdClass();
        $request->ReceiverAddress = new \stdClass();
        //Populate the Payment Information
        $request->BillingAccountNumber = $this->helper->getAPIAccountNumber();

        //Populate the Origin Information
        $request->SenderAddress->City = $this->helper->transliterateString($this->getRequest()->getShipperAddressCity());
        $request->SenderAddress->Province = $this->helper->transliterateString($this->getRequest()->getShipperAddressStateOrProvinceCode());
        $request->SenderAddress->Country = $this->helper->transliterateString($this->getRequest()->getShipperAddressCountryCode());
        $request->SenderAddress->PostalCode = $this->getRequest()->getShipperAddressPostalCode();

        //Populate the Desination Information
        $request->ReceiverAddress->City = $this->helper->transliterateString($this->getRequest()->getRecipientAddressCity());
        $request->ReceiverAddress->Province = $this->helper->transliterateString($this->getRequest()->getRecipientAddressStateOrProvinceCode());
        $request->ReceiverAddress->Country = $this->helper->transliterateString($this->getRequest()->getRecipientAddressCountryCode());
        $request->ReceiverAddress->PostalCode = str_replace(' ', '', $this->getRequest()->getRecipientAddressPostalCode());

        $response = $client->GetServicesOptions($request);
        $this->helper->checkResponse($response);

        return $response;
    }
}
