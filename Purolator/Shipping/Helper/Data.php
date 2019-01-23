<?php

namespace Purolator\Shipping\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data
{
    const TEST_MODE_CONFIG_PATH = 'carriers/purolator/is_test';
    const ACTIVATE_CONFIG_PATH = 'carriers/purolator/activate';

    const PRODUCTION_API_KEY_PASSWORD = 'carriers/purolator/api_key_password';
    const PRODUCTION_ACCOUNT_NUMBER = 'carriers/purolator/account_number';
    const PRODUCTION_REGISTERED_ACCOUNT_NUMBER = 'carriers/purolator/registered_account_number';
    const PRODUCTION_ACTIVATION_KEY = 'carriers/purolator/activation_key';
    const PRODUCTION_ACCESS_KEY = 'carriers/purolator/access_key';

    const TEST_ACCOUNT_NUMBER = '9999999999';
    const TEST_REGISTERED_ACCOUNT_NUMBER = '9999999999';

    const COLLINSHARPER_ACCESS_KEY = '32ba25d97c4240c181322956fd19312a';
    const COLLINSHARPER_ACCESS_PASSWORD = 'J*ddddOh ';

    const TEST_COLLINSHARPER_ACCESS_KEY = '42942e2dd2dc4eceb7c86dd69e195c38';
    const TEST_COLLINSHARPER_ACCESS_PASSWORD = '3^t#f1;/';

    const PUROLATOR_CONFIG_PATH = 'carriers/purolator';

    const KG = 'kg';
    const LB = 'lb';
    const OZ = 'oz';
    const GR = 'gr';

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /** @var EncryptorInterface  */
    protected $encryptor;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    protected $regionCollection;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $registry
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->regionCollection = $regionCollection;
        $this->registry = $registry;
        $this->messageManager = $messageManager;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getDefaultBoxSize($store = null)
    {
        return (string)$this->scopeConfig->getValue(self::PUROLATOR_CONFIG_PATH . '/box_size', ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return int
     */
    public function getCostMarkupSpecific($store = null)
    {
        return (int)$this->scopeConfig->getValue(self::PUROLATOR_CONFIG_PATH . '/markup_specific', ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return array
     */
    public function getAllowedMethods($store = null)
    {
        return explode(',', $this->scopeConfig->getValue(self::PUROLATOR_CONFIG_PATH . '/supported_services', ScopeInterface::SCOPE_STORE, $store));
    }

    /**
     * @param null $store
     * @return array
     */
    public function getMarkupOrDiscount($store = null)
    {
        return (float)$this->scopeConfig->getValue(self::PUROLATOR_CONFIG_PATH . '/markup_or_discount', ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isTestMode($store = null)
    {
        if ($store == null) {
            $store = $this->storeManager->getStore();
        }

        return $this->scopeConfig->isSetFlag(self::TEST_MODE_CONFIG_PATH, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAPIKeyPassword()
    {
        if ($this->isTestMode()) {
            return self::TEST_COLLINSHARPER_ACCESS_PASSWORD;
        }

        return $this->encryptor->decrypt($this->scopeConfig->getValue('carriers/purolator/api_password', ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAPIAccountNumber()
    {
        if ($this->isTestMode()) {
            return self::TEST_ACCOUNT_NUMBER;
        }

        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::PRODUCTION_ACCOUNT_NUMBER, ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return string
     */
    public function getAPIActivationKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::PRODUCTION_ACTIVATION_KEY, ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAccessKey()
    {
        if ($this->isTestMode()) {
            return self::TEST_COLLINSHARPER_ACCESS_KEY;
        }

        return $this->encryptor->decrypt($this->scopeConfig->getValue('carriers/purolator/api_key', ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRegisteredAccount()
    {
        if ($this->isTestMode()) {
            return self::TEST_REGISTERED_ACCOUNT_NUMBER;
        }

        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::PRODUCTION_REGISTERED_ACCOUNT_NUMBER,ScopeInterface::SCOPE_STORE));
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getConfig($key)
    {
        return $this->scopeConfig->getValue(self::PUROLATOR_CONFIG_PATH . '/' . $key, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getShippingOriginData()
    {
        return $this->scopeConfig->getValue('shipping/origin', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getStoreInformation()
    {
        return $this->scopeConfig->getValue('general/store_information',ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getStoreLocaleOptions()
    {
        return $this->scopeConfig->getValue('general/locale',ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getDefaultCountry()
    {
        return $this->scopeConfig->getValue('general/country/default',ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $txt
     * @return mixed
     */
    public function transliterateString($txt)
    {
        $transliterationTable = array(
            'á' => 'a',
            'Á' => 'A',
            'à' => 'a',
            'À' => 'A',
            'ă' => 'a',
            'Ă' => 'A',
            'â' => 'a',
            'Â' => 'A',
            'å' => 'a',
            'Å' => 'A',
            'ã' => 'a',
            'Ã' => 'A',
            'ą' => 'a',
            'Ą' => 'A',
            'ā' => 'a',
            'Ā' => 'A',
            'ä' => 'ae',
            'Ä' => 'AE',
            'æ' => 'ae',
            'Æ' => 'AE',
            'ḃ' => 'b',
            'Ḃ' => 'B',
            'ć' => 'c',
            'Ć' => 'C',
            'ĉ' => 'c',
            'Ĉ' => 'C',
            'č' => 'c',
            'Č' => 'C',
            'ċ' => 'c',
            'Ċ' => 'C',
            'ç' => 'c',
            'Ç' => 'C',
            'ď' => 'd',
            'Ď' => 'D',
            'ḋ' => 'd',
            'Ḋ' => 'D',
            'đ' => 'd',
            'Đ' => 'D',
            'ð' => 'dh',
            'Ð' => 'Dh',
            'é' => 'e',
            'É' => 'E',
            'è' => 'e',
            'È' => 'E',
            'ĕ' => 'e',
            'Ĕ' => 'E',
            'ê' => 'e',
            'Ê' => 'E',
            'ě' => 'e',
            'Ě' => 'E',
            'ë' => 'e',
            'Ë' => 'E',
            'ė' => 'e',
            'Ė' => 'E',
            'ę' => 'e',
            'Ę' => 'E',
            'ē' => 'e',
            'Ē' => 'E',
            'ḟ' => 'f',
            'Ḟ' => 'F',
            'ƒ' => 'f',
            'Ƒ' => 'F',
            'ğ' => 'g',
            'Ğ' => 'G',
            'ĝ' => 'g',
            'Ĝ' => 'G',
            'ġ' => 'g',
            'Ġ' => 'G',
            'ģ' => 'g',
            'Ģ' => 'G',
            'ĥ' => 'h',
            'Ĥ' => 'H',
            'ħ' => 'h',
            'Ħ' => 'H',
            'í' => 'i',
            'Í' => 'I',
            'ì' => 'i',
            'Ì' => 'I',
            'î' => 'i',
            'Î' => 'I',
            'ï' => 'i',
            'Ï' => 'I',
            'ĩ' => 'i',
            'Ĩ' => 'I',
            'į' => 'i',
            'Į' => 'I',
            'ī' => 'i',
            'Ī' => 'I',
            'ĵ' => 'j',
            'Ĵ' => 'J',
            'ķ' => 'k',
            'Ķ' => 'K',
            'ĺ' => 'l',
            'Ĺ' => 'L',
            'ľ' => 'l',
            'Ľ' => 'L',
            'ļ' => 'l',
            'Ļ' => 'L',
            'ł' => 'l',
            'Ł' => 'L',
            'ṁ' => 'm',
            'Ṁ' => 'M',
            'ń' => 'n',
            'Ń' => 'N',
            'ň' => 'n',
            'Ň' => 'N',
            'ñ' => 'n',
            'Ñ' => 'N',
            'ņ' => 'n',
            'Ņ' => 'N',
            'ó' => 'o',
            'Ó' => 'O',
            'ò' => 'o',
            'Ò' => 'O',
            'ô' => 'o',
            'Ô' => 'O',
            'ő' => 'o',
            'Ő' => 'O',
            'õ' => 'o',
            'Õ' => 'O',
            'ø' => 'oe',
            'Ø' => 'OE',
            'ō' => 'o',
            'Ō' => 'O',
            'ơ' => 'o',
            'Ơ' => 'O',
            'ö' => 'oe',
            'Ö' => 'OE',
            'ṗ' => 'p',
            'Ṗ' => 'P',
            'ŕ' => 'r',
            'Ŕ' => 'R',
            'ř' => 'r',
            'Ř' => 'R',
            'ŗ' => 'r',
            'Ŗ' => 'R',
            'ś' => 's',
            'Ś' => 'S',
            'ŝ' => 's',
            'Ŝ' => 'S',
            'š' => 's',
            'Š' => 'S',
            'ṡ' => 's',
            'Ṡ' => 'S',
            'ş' => 's',
            'Ş' => 'S',
            'ș' => 's',
            'Ș' => 'S',
            'ß' => 'SS',
            'ť' => 't',
            'Ť' => 'T',
            'ṫ' => 't',
            'Ṫ' => 'T',
            'ţ' => 't',
            'Ţ' => 'T',
            'ț' => 't',
            'Ț' => 'T',
            'ŧ' => 't',
            'Ŧ' => 'T',
            'ú' => 'u',
            'Ú' => 'U',
            'ù' => 'u',
            'Ù' => 'U',
            'ŭ' => 'u',
            'Ŭ' => 'U',
            'û' => 'u',
            'Û' => 'U',
            'ů' => 'u',
            'Ů' => 'U',
            'ű' => 'u',
            'Ű' => 'U',
            'ũ' => 'u',
            'Ũ' => 'U',
            'ų' => 'u',
            'Ų' => 'U',
            'ū' => 'u',
            'Ū' => 'U',
            'ư' => 'u',
            'Ư' => 'U',
            'ü' => 'ue',
            'Ü' => 'UE',
            'ẃ' => 'w',
            'Ẃ' => 'W',
            'ẁ' => 'w',
            'Ẁ' => 'W',
            'ŵ' => 'w',
            'Ŵ' => 'W',
            'ẅ' => 'w',
            'Ẅ' => 'W',
            'ý' => 'y',
            'Ý' => 'Y',
            'ỳ' => 'y',
            'Ỳ' => 'Y',
            'ŷ' => 'y',
            'Ŷ' => 'Y',
            'ÿ' => 'y',
            'Ÿ' => 'Y',
            'ź' => 'z',
            'Ź' => 'Z',
            'ž' => 'z',
            'Ž' => 'Z',
            'ż' => 'z',
            'Ż' => 'Z',
            'þ' => 'th',
            'Þ' => 'Th',
            'µ' => 'u',
            'а' => 'a',
            'А' => 'a',
            'б' => 'b',
            'Б' => 'b',
            'в' => 'v',
            'В' => 'v',
            'г' => 'g',
            'Г' => 'g',
            'д' => 'd',
            'Д' => 'd',
            'е' => 'e',
            'Е' => 'e',
            'ё' => 'e',
            'Ё' => 'e',
            'ж' => 'zh',
            'Ж' => 'zh',
            'з' => 'z',
            'З' => 'z',
            'и' => 'i',
            'И' => 'i',
            'й' => 'j',
            'Й' => 'j',
            'к' => 'k',
            'К' => 'k',
            'л' => 'l',
            'Л' => 'l',
            'м' => 'm',
            'М' => 'm',
            'н' => 'n',
            'Н' => 'n',
            'о' => 'o',
            'О' => 'o',
            'п' => 'p',
            'П' => 'p',
            'р' => 'r',
            'Р' => 'r',
            'с' => 's',
            'С' => 's',
            'т' => 't',
            'Т' => 't',
            'у' => 'u',
            'У' => 'u',
            'ф' => 'f',
            'Ф' => 'f',
            'х' => 'h',
            'Х' => 'h',
            'ц' => 'c',
            'Ц' => 'c',
            'ч' => 'ch',
            'Ч' => 'ch',
            'ш' => 'sh',
            'Ш' => 'sh',
            'щ' => 'sch',
            'Щ' => 'sch',
            'ъ' => '',
            'Ъ' => '',
            'ы' => 'y',
            'Ы' => 'y',
            'ь' => '',
            'Ь' => '',
            'э' => 'e',
            'Э' => 'e',
            'ю' => 'ju',
            'Ю' => 'ju',
            'я' => 'ja',
            'Я' => 'ja'
        );
        $txt = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
        return $txt;
    }

    /**
     * @param $id
     * @return \Magento\Framework\DataObject
     */
    public function getRegionById($id)
    {
        return $this->regionCollection->addFieldToFilter('main_table.region_id', $id)->getFirstItem();
    }

    /**
     * @param $tel
     * @return bool|string
     */
    public function getAreaCode($tel)
    {
        $tel = preg_replace('/[^\d]+/i', '', $tel);
        if (strlen($tel) > 0) {
            if (strlen($tel) > 10) {
                return substr($tel, -10, 3);
            }

            return substr($tel, 0, 3);
        }
    }

    /**
     * @param $tel
     * @return bool|string
     */
    public function getTel($tel)
    {
        $tel = preg_replace('/[^\d]+/i', '', $tel);

        if (strlen($tel) > 0) {
            if (strlen($tel) >= 10) {
                return substr($tel, -7);
            }
            if (strlen($tel) < 10) {
                //since the first 3 digits are taken for the area code , I will pad the incorrect number with zero's
                $num = substr($tel, 3);
                for ($i = 0; $i < (7 - strlen($num)); $i++) {
                    $num = $num . "0";
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getWeightMeasureUnit()
    {
        if ($this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE) == 'kgs') {
            return 'kg';
        } else {
            return 'lb';
        }
    }

    /**
     * @param $weight
     * @param $from_unit
     * @param $to_unit
     * @return float
     */
    public function getConvertedWeight($weight, $from_unit, $to_unit)
    {

        switch ($from_unit.'-'.$to_unit) {

            case 'kg-gram': $weight = 1000*$weight; break;

            case 'kg-lb': $weight = 2.21*$weight; break;

            case 'kg-oz': $weight = 35.27*$weight; break;

            case 'gram-kg': $weight = 0.001*$weight; break;

            case 'gram-lb': $weight = 0.0022*$weight; break;

            case 'gram-oz': $weight = 0.035*$weight; break;

            case 'lb-kg': $weight = 0.45*$weight; break;

            case 'lb-gram': $weight = 453.59*$weight; break;

            case 'lb-oz': $weight = 16*$weight; break;

            case 'oz-kg': $weight = 0.028*$weight; break;

            case 'oz-gram': $weight = 28.35*$weight; break;

            case 'oz-lb': $weight = 0.0625*$weight; break;

        }

        return round($weight, 3);

    }

    /**
     * @param $w
     * @param $u
     * @return float
     */
    public function getConvertedWeightLb($w, $u)
    {
        switch ($u) {
            case self::LB:
                $weight = round($w * 0.4535, 2);
                break;
            case self::GR:
                $weight = round($w * 0.001, 2);
                break;
            case self::OZ:
                $weight = round($w * 0.028349, 2);
                break;
            case self::KG:
            default:
                $weight = $w;
                break;
        }

        return $weight;
    }

    public function getDefaultPackageWeight()
    {
        return (float)$this->scopeConfig->getValue(self::PUROLATOR_CONFIG_PATH . '/default_package_weight');
    }

    /**
     * @param $response
     * @throws \Exception
     */
    public function checkResponse($response)
    {
        if (!empty($response->ResponseInformation->Errors->Error)) {

            foreach ($response->ResponseInformation->Errors as $error) {

                if (!is_array($error) && !empty($error->Description) ) {
                    $this->messageManager->addErrorMessage($error->Description);
                    break;
                }

                if (is_array($error)) {
                    foreach ($error as $item) {
                        $this->messageManager->addErrorMessage($item->Description);
                    }
                }
            }

            throw new \Magento\Framework\Exception\LocalizedException(__('Shipment error'));
        }
    }

    public function getConvertedSize($value, $from_unit, $to_unit)
    {

        switch ($from_unit.'-'.$to_unit) {

            case 'm-cm': $value = 100*$value; break;

            case 'm-mm': $value = 1000*$value; break;

            case 'm-inch': $value = 39.37*$value; break;

            case 'm-feet': $value = 3.28*$value; break;

            case 'cm-m': $value = 0.01*$value; break;

            case 'cm-mm': $value = 10*$value; break;

            case 'cm-inch': $value = 0.3937*$value; break;

            case 'cm-feet': $value = 0.0328*$value; break;

            case 'mm-m': $value = 0.001*$value; break;

            case 'mm-cm': $value = 0.1*$value; break;

            case 'mm-inch': $value = 0.03937*$value; break;

            case 'mm-feet': $value = 0.00328*$value; break;

            case 'inch-m': $value = 0.0254*$value; break;

            case 'inch-cm': $value = 2.54*$value; break;

            case 'inch-mm': $value = 25.4*$value; break;

            case 'inch-feet': $value = 0.083*$value; break;

            case 'feet-m': $value = 0.3048*$value; break;

            case 'feet-cm': $value = 30.48*$value; break;

            case 'feet-mm': $value = 304.8*$value; break;

            case 'feet-inch': $value = 12*$value; break;

        }

        return round($value, 1);

    }

    public function setNoLabelCreationFlag()
    {
        $this->registry->register('no_label_creation_flag', true);
    }

    public function getNoLabelCreationFlag()
    {
        return $this->registry->registry('no_label_creation_flag');
    }
    
    public function getTelCodeByCountry($countryCode)
    {
        $countryCodes = [
             'AF' => '93',
             'AX' => '35818',
             'NL' => '31',
             'AN' => '599',
             'AL' => '355',
             'DZ' => '213',
             'AS' => '685',
             'AD' => '376',
             'AO' => '244',
             'AI' => '1264',
             'AQ' => '672',
             'AG' => '1268',
             'AE' => '971',
             'AR' => '54',
             'AM' => '374',
             'AW' => '297',
             'AU' => '61',
             'AZ' => '994',
             'BS' => '1242',
             'BH' => '973',
             'BD' => '880',
             'BB' => '1242',
             'BE' => '32',
             'BZ' => '501',
             'BJ' => '229',
             'BM' => '1441',
             'BT' => '975',
             'BO' => '591',
             'BA' => '387',
             'BW' => '267',
             'BV' => '47',
             'BR' => '55',
             'GB' => '44',
             'IO' => '246',
             'VG' => '1284',
             'BN' => '673',
             'BG' => '359',
             'BF' => '226',
             'BI' => '257',
             'KY' => '1345',
             'CL' => '56',
             'CK' => '682',
             'CR' => '506',
             'DJ' => '253',
             'DM' => '1767',
             'DO' => '1809',
             'EC' => '593',
             'EG' => '20',
             'SV' => '503',
             'ER' => '291',
             'ES' => '34',
             'ZA' => '27',
             'GS' => '500',
             'KR' => '82',
             'ET' => '251',
             'FK' => '500',
             'FJ' => '679',
             'PH' => '63',
             'FO' => '298',
             'GA' => '241',
             'GM' => '220',
             'GE' => '995',
             'GH' => '233',
             'GI' => '350',
             'GD' => '1473',
             'GL' => '299',
             'GP' => '590',
             'GU' => '1671',
             'GT' => '502',
             'GG' => '44',
             'GN' => '224',
             'GW' => '245',
             'GY' => '592',
             'HT' => '509',
             'HM' => '61',
             'HN' => '504',
             'HK' => '852',
             'SJ' => '47',
             'ID' => '62',
             'IN' => '91',
             'IQ' => '964',
             'IR' => '98',
             'IE' => '353',
             'IS' => '354',
             'IL' => '972',
             'IT' => '39',
             'TL' => '670',
             'AT' => '43',
             'JM' => '1876',
             'JP' => '81',
             'YE' => '967',
             'JE' => '44',
             'JO' => '962',
             'CX' => '61',
             'KH' => '855',
             'CM' => '237',
             'CA' => '1',
             'CV' => '238',
             'KZ' => '7',
             'KE' => '254',
             'CF' => '236',
             'CN' => '86',
             'KG' => '996',
             'KI' => '686',
             'CO' => '57',
             'KM' => '269',
             'CG' => '242',
             'CD' => '243',
             'CC' => '61',
             'GR' => '30',
             'HR' => '385',
             'CU' => '53',
             'KW' => '965',
             'CY' => '357',
             'LA' => '856',
             'LV' => '371',
             'LS' => '266',
             'LB' => '961',
             'LR' => '231',
             'LY' => '218',
             'LI' => '423',
             'LT' => '370',
             'LU' => '352',
             'EH' => '21228',
             'MO' => '853',
             'MG' => '261',
             'MK' => '389',
             'MW' => '265',
             'MV' => '960',
             'MY' => '60',
             'ML' => '223',
             'MT' => '356',
             'IM' => '44',
             'MA' => '212',
             'MH' => '692',
             'MQ' => '596',
             'MR' => '222',
             'MU' => '230',
             'YT' => '262',
             'MX' => '52',
             'FM' => '691',
             'MD' => '373',
             'MC' => '377',
             'MN' => '976',
             'ME' => '382',
             'MS' => '1664',
             'MZ' => '258',
             'MM' => '95',
             'NA' => '264',
             'NR' => '674',
             'NP' => '977',
             'NI' => '505',
             'NE' => '227',
             'NG' => '234',
             'NU' => '683',
             'NF' => '672',
             'NO' => '47',
             'CI' => '255',
             'OM' => '968',
             'PK' => '92',
             'PW' => '680',
             'PS' => '970',
             'PA' => '507',
             'PG' => '675',
             'PY' => '595',
             'PE' => '51',
             'PN' => '870',
             'KP' => '850',
             'MP' => '1670',
             'PT' => '351',
             'PR' => '1',
             'PL' => '48',
             'GQ' => '240',
             'QA' => '974',
             'FR' => '33',
             'GF' => '594',
             'PF' => '689',
             'TF' => '33',
             'RO' => '40',
             'RW' => '250',
             'SE' => '46',
             'RE' => '262',
             'SH' => '290',
             'KN' => '1869',
             'LC' => '1758',
             'VC' => '1784',
             'BL' => '590',
             'MF' => '1599',
             'PM' => '508',
             'DE' => '49',
             'SB' => '677',
             'ZM' => '260',
             'WS' => '685',
             'SM' => '378',
             'SA' => '966',
             'SN' => '221',
             'RS' => '381',
             'SC' => '248',
             'SL' => '232',
             'SG' => '65',
             'SK' => '421',
             'SI' => '386',
             'SO' => '252',
             'LK' => '94',
             'SD' => '249',
             'FI' => '358',
             'SR' => '594',
             'CH' => '41',
             'SZ' => '268',
             'SY' => '963',
             'ST' => '239',
             'TJ' => '992',
             'TW' => '886',
             'TZ' => '255',
             'DK' => '45',
             'TH' => '66',
             'TG' => '228',
             'TK' => '690',
             'TO' => '676',
             'TT' => '1868',
             'TN' => '216',
             'TR' => '90',
             'TM' => '993',
             'TC' => '1649',
             'TV' => '688',
             'TD' => '235',
             'CZ' => '420',
             'UG' => '256',
             'UA' => '380',
             'HU' => '36',
             'UY' => '598',
             'NC' => '687',
             'NZ' => '64',
             'UZ' => '998',
             'BY' => '375',
             'VU' => '678',
             'VA' => '39',
             'VE' => '58',
             'RU' => '7',
             'VN' => '84',
             'EE' => '372',
             'WF' => '681',
             'US' => '1',
             'VI' => '1340',
             'UM' => '1',
             'ZW' => '263',
            ];

        return $countryCodes[$countryCode];
    }
}
