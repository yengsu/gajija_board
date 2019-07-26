<?php
namespace Gajija\service\_traits ;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Service- Email 보내기 (PHPMailer)
 * 
  */
trait Service_PHPMailer_Trait
{
		/**
		 * Mail - MX domain name
		 * @var string
		 */
		
		protected $Mail = null ;
		
		protected function PHPMailer_load()
		{
			require_once "PHPMailer/vendor/autoload.php" ;
			$this->Mail = new PHPMailer() ;
		}
		/**
		 * 
		 * @example $post_data = array(
					'from'=> array('email'=>이메일주소, 'name'=>발신자명), // 발신자
					'to'=> array('email'=>이메일주소, 'name'=>수신자명), // 수신자
					'subject'=>$subject, // 제목
					'html'=>$html, // 내용(html)
					'text'=>$text, // 내용(텍스트)
					'attachment' => 'image/2.png' // 첨부파일
				) ;
		 */
		protected function sendMailByPHPMailer( array $DATA )
		{
			
			if( !is_object($this->Mail) ) $this->PHPMailer_load() ;
			else $this->Mail->clearAddresses(); 
			
			$POST_DATA = $DATA;
			if( ! isset($POST_DATA["from"]["email"]) ) {
				$POST_DATA["from"] = array() ;
				$POST_DATA["from"]["email"] = $DATA["from"] ;
			}
			if( ! isset($POST_DATA["to"]["email"]) ) {
				//$POST_DATA["to"] = array() ;
				$POST_DATA["to"]["email"] = $DATA["to"] ;
			}
			//echo '11<pre>';print_r($POST_DATA);exit;
			//Create a new PHPMailer instance
			//$mail = new PHPMailer;
			//Tell PHPMailer to use SMTP
			$this->Mail->CharSet = "UTF-8";
			$this->Mail->Encoding = "base64";
			$this->Mail->isSMTP();
			//Enable SMTP debugging
			// 0 = off (for production use)
			// 1 = client messages
			// 2 = client and server messages
			$this->Mail->SMTPDebug = 0;
			//Set the hostname of the mail server
			$this->Mail->Host = 'smtp.gmail.com';
			// use
			// $mail->Host = gethostbyname('smtp.gmail.com');
			// if your network does not support SMTP over IPv6
			//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
			$this->Mail->Port = 465;//587;
			//Set the encryption system to use - ssl (deprecated) or tls
			$this->Mail->SMTPSecure = 'ssl';//'tls';//
			//Whether to use SMTP authentication
			$this->Mail->SMTPAuth = true;
			//Username to use for SMTP authentication - use full email address for gmail
			$this->Mail->Username = MASTER_EMAIL['email'];
			//Password to use for SMTP authentication
			$this->Mail->Password = MASTER_EMAIL['pwd'];
			//Set who the message is to be sent from
			$this->Mail->setFrom(MASTER_EMAIL['email'], MASTER_EMAIL['author']);
			//Set an alternative reply-to address
			//$mail->addReplyTo($POST_DATA["from"]["email"], $POST_DATA["from"]["name"]);
			//$this->Mail->addReplyTo($POST_DATA["from"]["email"], $POST_DATA["from"]["name"]);
			//Set who the message is to be sent to
			$this->Mail->addAddress($POST_DATA["to"]["email"], $POST_DATA["to"]["name"]);
			//Set the subject line
			$this->Mail->Subject = $POST_DATA["subject"];
			//Read an HTML message body from an external file, convert referenced images to embedded,
			//convert HTML into a basic plain-text alternative body
			//$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
			if( !empty($POST_DATA["html"]) ) $this->Mail->msgHTML($POST_DATA["html"]);

			//Replace the plain text body with one created manually
			$this->Mail->AltBody = 'This is a plain-text message body';
			//if( !empty($POST_DATA["text"]) ) $mail->AltBody = $POST_DATA["text"]; // 텍스트내용
			//Attach an image file
			//$mail->addAttachment('images/phpmailer_mini.png');
			if( !empty($POST_DATA["attachment"]) ) {
				
				//첨부파일이 2개이상인경우 (배열로 받아서 추가)
				if( is_array($POST_DATA["attachment"]) )
				{
					foreach( $POST_DATA["attachment"] as $attachFile )
					{
						$this->Mail->addAttachment( $attachFile );
					}
				}
				// 첨부파일이 1개인 경우
				else{
					$this->Mail->addAttachment($POST_DATA["attachment"]);
				}
			}
			
			$res = $this->Mail->send();
			
			//send the message, check for errors
			if (!$res) {
				throw new \Exception($this->Mail->ErrorInfo);
			} else {
				//echo "Message sent!";
				return $res ;
				
			}
			
		}
}