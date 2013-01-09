<?php
class phone {

  static private $phone_util;

  static function init(){
    require_once 'libphonenumber/PhoneNumberUtil.php';
    require_once 'libphonenumber/RegionCode.php';
    require_once 'libphonenumber/PhoneNumber.php';
    require_once 'libphonenumber/CountryCodeToRegionCodeMap.php';

    self::$phone_util = \com\google\i18n\phonenumbers\PhoneNumberUtil::getInstance();
  }

  public static function get(){
    return self::$phone_util;
  }

  /**
  * Compute the international number for phone number and country code.
  *
  * @param mixed $phone_number
  * @param mixed $code_region
  */
  public static function international_number($phone_number, $code_region) {
    $phoneNumber = self::parse($phone_number, $code_region);
    $international_number = self::$phone_util->format($phoneNumber, com\google\i18n\phonenumbers\PhoneNumberFormat::INTERNATIONAL);
    return $international_number;
  }

  public static function format_E164($phone_number, $code_region) {
    $phoneNumber = self::parse($phone_number, $code_region);
    return self::$phone_util->format($phoneNumber, com\google\i18n\phonenumbers\PhoneNumberFormat::E164);
  }

  /**
  * Validate user input
  *
  * @param string $phone_number
  * @param string $code_region
  *
  * @return string for DB storage
  */
  public static function validate($phone_number, $code_region){
    $phoneNumber = self::parse($phone_number, $code_region);
    $international_number = self::format($phoneNumber, com\google\i18n\phonenumbers\PhoneNumberFormat::INTERNATIONAL);

    if(0 != strcasecmp($international_number, $phone_number))
      Throw new Exception('Incorrect format shoulb be :'.$international_number);

    return self::format($phoneNumber, com\google\i18n\phonenumbers\PhoneNumberFormat::E164);
  }

  public static function format($phoneNumber, $format = com\google\i18n\phonenumbers\PhoneNumberFormat::E164){
    return self::$phone_util->format($phoneNumber, $format);
  }

  /**
  * Parse string phone number to object phonenumber
  *
  * @param string $phone_number
  * @param string $code_region
  *
  * return Phonenumber object
  */
  public static function parse($phone_number, $code_region){

    if(!self::$phone_util->isViablePhoneNumber($phone_number))
      Throw new Exception('Not a viable number');

    $testRegion = '';

    //test avec reconnaissance automatique
    try{
      $NumberProto = self::$phone_util->parse('+'.$phone_number, 'ZZ');
      $testRegion = self::$phone_util->getRegionCodeForNumber($NumberProto);
    }catch(Exception $e) {}

    //sinon on force avec les infos
    if($testRegion != strtoupper($code_region)){
      $NumberProto = self::$phone_util->parse($phone_number, strtoupper($code_region));
    }

    return $NumberProto;
  }

  /**
  * Format phone number for output from database
  *
  * @param string $phone_number
  * @param string $code_region
  *
  */
  public static function output($phone_number, $code_region){
    if(!$phone_number || !$code_region)
      return '';

    if(!self::$phone_util->isViablePhoneNumber($phone_number))
      Throw new Exception('Not a viable number');

    $NumberProto = self::$phone_util->parse($phone_number, strtoupper($code_region));

    if(self::$phone_util->isValidNumberForRegion($NumberProto, $code_region))
      Throw new Exception('Not a valid Number for region :'.$code_region);

    return self::$phone_util->format($NumberProto, com\google\i18n\phonenumbers\PhoneNumberFormat::INTERNATIONAL);
  }

  /**
  * Format phone number for display
  *
  * @param string $phone_number
  * @param string $code_region
  */
  public static function display($phone_number, $code_region = null){
    try{
      return self::output($phone_number, $code_region);
    }
    catch(Exception $e){
      return $phone_number;
    }
  }

}