<?php

/* Main handling class for the exerciseR interface.
 */
class MailHandler {

    public $LoginHandler = NULL;
    //public $DbHandler    = NULL;

    protected $_post        = NULL;
    protected $_config      = NULL;

    // Use 'true' for admin pages, 'false' for participant pages.
    // Used to check if the current user has enough privileges
    // to see this page.
    protected $admin_page = true;

    /* Setting up ExerciseR object.
     *
     * The ExerciseR object is the main object for the UI/UX.
     * Sets up the database connection, grants permission (LoginHandler)
     * to the UI, and loads user information (UserClass).
     * In addition, an ExerciseHandler object is initialized (handling
     * Exercises, Overview, ...).
     *
     * Parameters
     * ==========
     * config : ConfigParser
     *      object with UI/UX configuration.
     */
    function __construct($config, $admin_page = true) {

        // Default time zone handling
        date_default_timezone_set("UTC");
        $this->admin_page = $admin_page;

        // Append ConfigParser object
        $this->_config       = $config;

        $this->DbHandler = new DbHandler($config);

        // Create LoginHandler, does login check on construct
        $this->LoginHandler = new LoginHandler($this->DbHandler);

        // If logn was successful: add UserClass to the object
        $this->UserClass = new UserClass($_SESSION["user_id"], $this->DbHandler);

        // If this is an admin page but the user has no admin privileges.
        // show an error.
        if ($this->admin_page & !$this->UserClass->is_admin()) {
            $this->LoginHandler->access_denied();
        }

    }


    function send($receiver, $receiver_name, $subject, $body) {

        require_once("./class.phpmailer.php");
        require_once("./class.smtp.php");

        // Requires PHPMailer to be installed!
        $mail = new PHPMailer();
        $mail->IsSMTP();                       // telling the class to use SMTP
        
        $mail->Host       =  $this->_config->get("phpmailer", "host");   // sets Gmail as the SMTP server
        $mail->SMTPAuth   =  $this->_config->get("phpmailer", "auth");   // enable SMTP authentication

        $mail->SMTPSecure = $this->_config->get("phpmailer", "secure") == 1 ? true : false;
        $mail->Port       = $this->_config->get("phpmailer", "port");    // set the SMTP port for the GMAIL

        
        $mail->Username   = $this->_config->get("phpmailer", "user");
        $mail->Password   = $this->_config->get("phpmailer", "password");
        
        $mail->CharSet = "utf08"; ####'windows-1250';
        $mail->SetFrom($this->_config->get("phpmailer", "sender"),
                       $this->_config->get("phpmailer", "sender_name"));
        $mail->Subject = $subject;
        $mail->ContentType = 'text/plain';
        $mail->IsHTML(false);
        
        $mail->Body = $body;
        
        $mail->AddAddress($receiver, $receiver_name);
        $mail->SMTPDebug = 0;
        //print_r($mail);
        
        if (!$mail->Send()) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            $res = false;
        } else {
            $res = true;
        }
        return($res);

    }
}


