<?php

/**
 * Collection of upgrade steps
 */
class CRM_Correctaddressee_Upgrader extends CRM_Correctaddressee_Upgrader_Base {

  public function enable() {
    CRM_Core_BAO_Setting::setItem('1000', 'Extension', 'nl.sp.correctaddressee:version');
  }

  const BATCH_SIZE = 250;

  public function upgrade_1001() {
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
    $contact = CRM_Core_DAO::executeQuery("SELECT id, contact_type FROM `civicrm_contact` WHERE `addressee_display` LIKE 'T.a.v.%' AND `id` BETWEEN %1 AND %2", array(1=>array($startId, 'Integer'), 2=>array($endId,'Integer')));
    while ($contact->fetch()) {
      CRM_Contact_BAO_Contact_Utils::updateGreeting(array(
        'id' => $contact->id,
        'gt' => 'addressee',
        'ct' => $contact->contact_type,
        'force' => 1,
      ));
    }
    return true;
  }

}
