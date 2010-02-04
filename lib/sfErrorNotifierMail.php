<?php

/*
 * (c) 2009 Gustavo Garcia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Daniele Occhipinti <>
 */
class sfErrorNotifierMail{

  protected $body;
  protected $to;
  protected $from;
  protected $subject;
  protected $headers = '';
  protected $data = array();
  protected $exception;
  protected $context;
  protected $env;

  public function __construct($to, $subject = null, $data = array(), $exception=null, $context = null)
  {

    $this->to = $to;
    
    
    if($subject){
      $this->subject = $subject;
    }
    else{
      $this->subject = 'Symfony error';
    }

    if ($conf = $context->getConfiguration()){
      $this->env = $conf->getEnvironment();
    }

    $this->context = $context;
    $this->data = $data;
    $this->exception = $exception;
  }

  private function addRow($th, $td='&nbsp;')
  {
    $this->body .= '
      <tr style="padding: 4px;spacing: 0;text-align: left;">
        <th style="background:#cccccc" width="100px">'.$th.':</th>
        <td style="padding: 4px;spacing: 0;text-align: left;background:#eeeeee">'.nl2br($td).'</td>
      </tr>';
  }

  private function addTitle($title)
  {
    $this->body .= '<h1 style="background: #0055A4; color:#ffffff;padding:5px;">'.$title.'</h1>';
  }

  private function beginTable()
  {
    $this->body .= '<table cellspacing="1" width="100%">';
  }

  public function notify()
  {
    
    //Initialize the body message
    $this->body = '<div style="font-family: Verdana, Arial;">';

    //The exception resume
    $this->addTitle('Resume');

    $this->beginTable();
    if ($this->exception)
    {
      $this->addRow('Message',$this->exception->getMessage());
    }
    else
    {
      $this->addRow('Subject',$this->subject);
    }
    $this->addRow('Environment', $this->env);
    $this->addRow('Generated at' , date('H:i:s j F Y'));
    $this->body .= '</table>';


    //The exception itself
    if ($this->exception)
    {
      $this->addTitle('Exception');

      $this->beginTable();
      $this->addRow('Trace',$this->exception);

      $this->body .= '</table>';
    }

    //Aditional Data
    $this->addTitle('Additional Data');
    $this->beginTable();
    foreach($this->data as $key=>$value){
      $this->addRow($key,$value);
    }
    $this->body .= '</table>';


    // REQUEST PARAMETER
    if(count($_POST)){
      $this->addTitle('$_POST Data');
      $this->beginTable();
      foreach($_POST as $key => $value){
        $this->addRow($key, $value);
      }
      $this->body .'</table>';
    }

    if(count($_GET)){
      $this->addTitle('$_GET Data');
      $this->beginTable();
      foreach($_GET as $key => $value){
        $this->addRow($key, $value);
      }
      $this->body .'</table>';
    }

    //User attributes and credentials
    $this->addTitle('User');
    $this->beginTable();

    $user = $this->context->getUser();
     
    $subtable = array();
    foreach ($user->getAttributeHolder()->getAll() as $key => $value){
      if (is_array($value))
      {
        $value = 'Array: ' . var_export($value,true);
      }
      $subtable[] = '<b>'.$key.'</b>: '.$value;
    }
     
    $subtable = implode('<br/>',$subtable);
     
    $this->addRow('Attributes',$subtable);
    $this->addRow('Credentials',implode(', ',$user->getCredentials()));
     
    $this->body .= '</table>';
     
    $this->body .= '</div>';


    $mail = $this->context->getMailer()->compose($this->to, $this->to, $this->subject,'');
    $mail->setBody($this->body, 'text/html');

    $this->context->getMailer()->send($mail);
    return true;
  }

}