<?php

/**
 * Collection of upgrade steps
 */
class CRM_Correctaddressee_Upgrader extends CRM_Correctaddressee_Upgrader_Base {

  public function enable() {
    CRM_Core_BAO_Setting::setItem('1000', 'Extension', 'nl.sp.correctaddressee:version');
  }

  const BATCH_SIZE = 250;

  public function upgrade_1002() {
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_contact');
    for ($startId = 0; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Correct addressesee (%1 / %2)', array(
        1 => $startId,
        2 => $maxId,
      ));
      $this->addTask($title, 'correct', $startId, $endId);
    }

    return true;
  }

  public static function correct($startId, $endId) {
    $contact = CRM_Core_DAO::executeQuery("SELECT id FROM `civicrm_contact` WHERE `contact_type` = 'Individual' AND `addressee_display` LIKE 'T.a.v.%' AND `id` BETWEEN %1 AND %2", array(1=>array($startId, 'Integer'), 2=>array($endId,'Integer')));
    while ($contact->fetch()) {
      self::resetAddressee($contact->id);
    }
    return true;
  }

  public static function resetAddressee($contact_id) {
    $filter = array(
      'contact_type' => 'Individual',
      'greeting_type' => 'addressee',
    );

    $allGreetings = CRM_Core_PseudoConstant::greeting($filter);
    $originalGreetingString = $greetingString = CRM_Utils_Array::value(1, $allGreetings);
    if (!$greetingString) {
      CRM_Core_Error::fatal(ts('Incorrect greeting value id %1, or no default greeting for this contact type and greeting type.', array(1 => 1)));
    }

    // build return properties based on tokens
    $greetingTokens = CRM_Utils_Token::getTokens($greetingString);
    $tokens = CRM_Utils_Array::value('contact', $greetingTokens);
    $greetingsReturnProperties = array();
    if (is_array($tokens)) {
      $greetingsReturnProperties = array_fill_keys(array_values($tokens), 1);
    }

    $extraParams[] = array('contact_type', '=', 'Individual', 0, 0);
    list($greetingDetails) = CRM_Utils_Token::getTokenDetails(array($contact_id),
      $greetingsReturnProperties,
      FALSE, FALSE, $extraParams
    );

    CRM_Utils_Token::replaceGreetingTokens($greetingString, $greetingDetails[$contact_id], $contact_id, 'CRM_UpdateGreeting');


    $sql = "UPDATE `civicrm_contact` SET `addressee_custom` = null, `addressee_id` = 1, `addressee_display` = %1 where id = %2";
    $sqlParams[1] = array($greetingString, 'String');
    $sqlParams[2] = array($contact_id, 'Integer');

    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

}
