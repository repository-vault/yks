<?php

  class calendar {

    //Public API access
    public $authUrl;
    private $client;
    private $service;
    private $authentification_type;
    private $service_google_token;

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

      if($this->authentification_type == 'oauth2.0_service' && (!strlen(yks::$get->config->google->calendar->oauth->service['client_id']) || !strlen(yks::$get->config->google->calendar->oauth->service['email_address']) || !strlen(yks::$get->config->google->calendar->oauth->service['key_file']))) return missingServiceAccountDetailsWarning();
      if($this->authentification_type == 'oauth2.0_application' && (!strlen(yks::$get->config->google->calendar->oauth->application['client_id']) || !strlen(yks::$get->config->google->calendar->oauth->application['email_address']) || !strlen(yks::$get->config->google->calendar->oauth->application['redirect_uri']))) return missingServiceAccountDetailsWarning();

      if($this->authentification_type == 'public_server') $this->client->setDeveloperKey(yks::$get->config->google->calendar->public['key']);

      if($this->authentification_type == 'oauth2.0_service') {
        $this->service_google_token = pick($_SESSION['service_google_token'], $this->service_google_token);
        if($this->service_google_token)
          $this->client->setAccessToken($this->service_google_token);

        $key_file_content = file_get_contents(ROOT_PATH.yks::$get->config->google->calendar->oauth->service['key_file']);
        $cred                   = new Google_Auth_AssertionCredentials(
          yks::$get->config->google->calendar->oauth->service['email_address'],
          array('https://www.googleapis.com/auth/calendar'),
          $key_file_content
        );
        $this->client->setAssertionCredentials($cred);

        if($this->client->getAuth()->isAccessTokenExpired()) {
          $this->client->getAuth()->refreshTokenWithAssertion($cred);
        }

        $this->service_google_token = $_SESSION['service_google_token'] = $this->client->getAccessToken();
      }

      if($this->authentification_type == 'oauth2.0_application') {
        $this->client->setClientId(yks::$get->config->google->calendar->oauth->application['client_id']);
        $this->client->setClientSecret(yks::$get->config->google->calendar->oauth->application['client_secret']);
        $this->client->setRedirectUri(yks::$get->config->google->calendar->oauth->application['redirect_uri']);
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

      if($this->authentification_type == 'oauth2.0_application' && !$this->client->getAccessToken()) $this->authUrl = $this->client->createAuthUrl();
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
     * @param array  $attendees_users
     * @param date   $date       (YYYY-MM-DD)
     * @param time   $time_start (HH:MM:SS)
     * @param time   $time_end
     * @param string $color_id
     * @param string $timezone
     * @param array  $optParams
     *
     * @return string Inserted event ID
     */
    public function add_event($calendar_id, $title, $location, $description, $attendees_users, $date, $time_start, $time_end, $color_id, $timezone = "Europe/Paris", $optParams = array()) {
      $event = new Google_Service_Calendar_Event();

      $event->setSummary($title);
      $event->setLocation($location);
      $event->setDescription($description);
      if($color_id) $event->setColorId($color_id);

      $attendees = array();

      if($attendees_users) foreach($attendees_users as $user_mail => $user_infos) {
        $attendee = new Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($user_mail);
        $attendee->setDisplayName($user_infos['name']);
        $attendee->setResource($user_infos['resource']);
        $attendees[] = $attendee;
      }

      $event->attendees = $attendees;

      $start          = new Google_Service_Calendar_EventDateTime();
      $start_datetime = ($time_start) ? $date.'T'.$time_start : $date;
      $start->setDateTime($start_datetime);
      $start->setTimeZone($timezone);
      $event->setStart($start);

      $end          = new Google_Service_Calendar_EventDateTime();
      $end_datetime = ($time_start) ? $date.'T'.$time_end : $date;
      $end->setDateTime($end_datetime);
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

      if($attendees_users) foreach($attendees_users as $user_mail => $user_infos) {
        $attendee = new Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($user_mail);
        $attendee->setDisplayName($user_infos['name']);
        $attendee->setResource($user_infos['resource']);
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