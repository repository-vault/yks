<?php

  class calendar {

    //Public API access
    public $authUrl;

    //OAuth 2.0 Service

    private $key = 'AIzaSyDK6HCGXdHLJH-_ajXz1xmJmM7X5RbKHXk';

    private $client_id_service = '324105369659-0af7mss9isb5vdgnpvi3f0h60jhv0nru.apps.googleusercontent.com';

    private $service_account_name_service = '324105369659-0af7mss9isb5vdgnpvi3f0h60jhv0nru@developer.gserviceaccount.com';

    private $key_file_location = '/rsrcs/Intervenants_Agenda-6289a260f638.p12';

    //OAuth 2.0 Application

    private $key_file_content;

    private $client_id_application = '324105369659-7uud1ll9q08nfeitu7d1qjstes1n2n8r.apps.googleusercontent.com';

    private $service_account_name_application = '324105369659-7uud1ll9q08nfeitu7d1qjstes1n2n8r@developer.gserviceaccount.com';

    private $client_secret = 'cpQ2Cjc7Z6u5popqMO2daBQU';

    private $redirect_uri = 'http://crm.klambard.si.ivsdev.net/?/Crm/Client/oauth2callback//';

    private $token_data;

    private $client;

    private $service;

    private $authentification_type;

    /**
     * Constructor
     *
     * @param string $authentification_type (default: oauth2.0_service [oauth2.0_application, public_server])
     */
    function __construct($authentification_type = 'oauth2.0_service') {
      require_once 'Google/Client.php';
      require_once 'Google/Service/Calendar.php';

      $this->authentification_type = $authentification_type;

      $this->client = new Google_Client();
      $this->client->setApplicationName("Intervenant_Calendar");
      $this->service = new Google_Service_Calendar($this->client);

      if($this->authentification_type == 'oauth2.0_service' && (!strlen($this->client_id_service) || !strlen($this->service_account_name_service) || !strlen($this->key_file_location))) return missingServiceAccountDetailsWarning();
      if($this->authentification_type == 'oauth2.0_application' && (!strlen($this->client_id_application) || !strlen($this->service_account_name_application) || !strlen($this->redirect_uri))) return missingServiceAccountDetailsWarning();

      if($this->authentification_type == 'public_server') $this->client->setDeveloperKey($this->key);

      if($this->authentification_type == 'oauth2.0_service') {
        if(isset($_SESSION['service_google_token'])) {
          $this->client->setAccessToken($_SESSION['service_google_token']);
        }

        $this->key_file_content = file_get_contents(ROOT_PATH.$this->key_file_location);
        $cred                   = new Google_Auth_AssertionCredentials($this->service_account_name_service, array('https://www.googleapis.com/auth/calendar'), $this->key_file_content);
        $this->client->setAssertionCredentials($cred);

        if($this->client->getAuth()->isAccessTokenExpired()) {
          $this->client->getAuth()->refreshTokenWithAssertion($cred);
        }

        $_SESSION['service_google_token'] = $this->client->getAccessToken();
      }

      if($this->authentification_type == 'oauth2.0_application') {
        $this->client->setClientId($this->client_id_application);
        $this->client->setClientSecret($this->client_secret);
        $this->client->setRedirectUri($this->redirect_uri);
        $this->client->setScopes('https://www.googleapis.com/auth/calendar');
        $this->client->setAccessType('offline');

        if(isset($_SESSION['code_oauth2_google']) && $_SESSION['code_oauth2_google'] != "/Client/oauth2callback//") {
          $this->client->authenticate($_SESSION['code_oauth2_google']);
          $_SESSION['application_google_token'] = $this->client->getAccessToken();
          unset($_SESSION['code_oauth2_google']);
        }

        if(isset($_SESSION['application_google_token']) && $_SESSION['application_google_token']) {
          $this->client->setAccessToken($_SESSION['application_google_token']);
        }

        if($this->client->isAccessTokenExpired()) {
          $new_access_token = json_decode($this->client->getAccessToken());
          $this->client->refreshToken($new_access_token->refresh_token);
        }
      }

      if($this->client->getAccessToken()) {
      }
      else {
        $this->authUrl = $this->client->createAuthUrl();
      }
    }

    /**
     * Return the object client
     */
    public function get_client() {
      return $this->client;
    }

    /**
     * Return the object service
     */
    public function get_service() {
      return $this->service;
    }

    /**
     * Add an event in a Google calendar
     *
     * @param string $calendar_id
     * @param string $title
     * @param string $location
     * @param array  $attendees_user_mail
     * @param date   $date       (YYYY-MM-DD)
     * @param time   $time_start (HH:MM:SS)
     * @param time   $time_end
     * @param string $color_id
     * @param string $timezone
     * @param array  $optParams
     *
     * @return string Inserted event ID
     */
    public function add_event($calendar_id, $title, $location, $description, $attendees_user_mail, $date, $time_start, $time_end, $color_id, $timezone = "Europe/Paris", $optParams = array()) {
      $event = new Google_Service_Calendar_Event();

      $event->setSummary($title);
      $event->setLocation($location);
      $event->setDescription($description);
      if($color_id) $event->setColorId($color_id);

      $attendees = array();

      if($attendees_user_mail) foreach($attendees_user_mail as $user_mail => $user_name) {
        $attendee = new Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($user_mail);
        $attendee->setDisplayName($user_name);
        $attendees[] = $attendee;
      }

      $event->attendees = $attendees;

      $start = new Google_Service_Calendar_EventDateTime();
      $start->setDateTime($date.'T'.$time_start);
      $start->setTimeZone($timezone);
      $event->setStart($start);

      $end = new Google_Service_Calendar_EventDateTime();
      $end->setDateTime($date.'T'.$time_end);
      $end->setTimeZone($timezone);
      $event->setEnd($end);

      $inserted_event = $this->service->events->insert($calendar_id, $event, $optParams);

      return $inserted_event->getId();
    }

    /**
     * Update an event in a Google calendar
     *
     * @param string $calendar_id
     * @param string $event_id
     * @param string $title
     * @param string $location
     * @param array  $attendees_user_mail
     * @param date   $date
     * @param time   $time_start
     * @param time   $time_end
     * @param string $color_id
     * @param string $timezone
     * @param array  $optParams
     *
     * @return string Updated event ID
     */
    public function update_event($calendar_id, $event_id, $title, $location, $description, $attendees_user_mail, $date, $time_start, $time_end, $color_id, $timezone = "Europe/Paris", $optParams = array()) {
      $event = $this->service->events->get($calendar_id, $event_id);

      $event->setSummary($title);
      $event->setLocation($location);
      $event->setDescription($description);
      if($color_id) $event->setColorId($color_id);

      $attendees = array();

      if($attendees_user_mail) foreach($attendees_user_mail as $user_mail => $user_name) {
        $attendee = new Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($user_mail);
        $attendee->setDisplayName($user_name);
        $attendees[] = $attendee;
      }

      $event->attendees = $attendees;

      $start = new Google_Service_Calendar_EventDateTime();
      $start->setDateTime($date.'T'.$time_start);
      $start->setTimeZone($timezone);
      $event->setStart($start);

      $end = new Google_Service_Calendar_EventDateTime();
      $end->setDateTime($date.'T'.$time_end);
      $end->setTimeZone($timezone);
      $event->setEnd($end);

      $updated_event = $this->service->events->update($calendar_id, $event_id, $event, $optParams);

      return $updated_event->getId();
    }

    /**
     * Delete an event in a Google calendar
     *
     * @param string $calendar_id
     * @param string $event_id
     * @param array  $optParams
     */
    public function delete_event($calendar_id, $event_id, $optParams = array()) {
      $this->service->events->delete($calendar_id, $event_id, $optParams);
    }
  }