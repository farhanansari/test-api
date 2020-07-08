<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>      
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
    <head>
        <meta name="viewport" content="width=device-width" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    </head>
    <body style="background:#f6f6f6 ">
        <table width="600" cellpadding="0" cellspacing="0" align="center" >
            <tbody>
                <tr>
                 <?php 
                  echo $this->element('PCEmail/header',['subject' => $var['subject']]);
                  ?>
                </tr>
                <tr>
                    <td style="padding:7px 0px 12px;" >
                        <h6 style="padding:0px 0px 14px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">
                            <?= $var['title']; ?></h6>
                        <p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; "><?= $var['message']; ?></p>
                    </td>
                </tr>
                <?php $statusFlagVal = array (2,3,4,5);
                if( in_array($var['statusFlag'],$statusFlagVal)) { ?>
                <tr>
			<td style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size: 16px; margin: 0; vertical-align: bottom; padding:0px 0px 0px; border-bottom:1px solid #3054ba;">
			<p style="padding:0px 0px 3px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">Details</p>
			</td>
		</tr>
                <tr>
		    <td>
			<table width="100%">
				<tbody>
				<tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Job ID #</span> <?php echo $jobID = isset($var['joborder_id']) ? $var['joborder_id']: 'NIL' ;?>
                                </p>
				</td>

				<td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;text-align: right;">
                                    <span style="color:#2f5496;">Job Title #</span> <?php echo $jobTitle = isset($var['jobTitle']) ? $var['jobTitle'] : 'NIL' ;?>
                                </p>
				</td>
				</tr>
                                <?php if(!empty($var['statusFlag']) && ($var['statusFlag'] == 3) ) { ?>
                                <tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Company #</span> <?php echo $employer = isset($var['employer']) ? $var['employer'] : 'NIL' ;?>
                                </p>
				</td>

				<td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;text-align: right;">
                                    <span style="color:#2f5496;">Location #</span> <?php echo $location = isset($var['location']) ? $var['location'] : 'NIL' ;?>
                                </p>
				</td>
				</tr>    
                                <?php } ?>
				</tbody>
			</table>
		    </td>
		</tr>
                <?php } ?>
                <tr>
                    <td>
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size: 16px; margin: 0;  padding:4px 0px 10px; ">
                                        <?php
                                        $redirectionText = (isset($var['statusFlag'])) ? $var['statusFlag'] : "";
                                        switch ($redirectionText) {
                                            case 0 :
                                                $message = "Validate Email Address";
                                                break;
                                            case 1 :
                                                $message = "Complete Your Profile" ;
                                                break;
                                            case 2 :
                                                $message = "Submit Performance Rating";
                                                break;
                                            case 12 :
                                                $message = "Validate Email Address";
                                                break;
                                            case 13 :
                                                $message = "Validate Email Address";
                                                break;
                                            default :
                                                $message = "";
                                                break;
                                        }
                                        ?>
                                        <?php if(isset($var['verifyLink'])) { ?>
                                        <a href="<?= $var['verifyLink']; ?>" style="border-radius: 6px; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;  background-color:#4472c4; padding:6px 45px; border:1px solid #2d4570; color:#000; cursor:pointer; outline:none; font-weight:bold;text-decoration: none;"><?= $message; ?></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:7px 0px 30px;">
                        <h5 style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 13px; margin:0px;  font-weight:normal;">
                            <?php
                                $bottomText = (isset($var['statusFlag'])) ? $var['statusFlag'] : "";
                                switch ($bottomText) {
                                    case 0 :
                                        $message = "Once you have validated your email address (required), a PeopleCaddie representative will contact you shortly to follow up.";
                                        break;
                                    case 1 :
                                        $message = "We are here to assist if you need us." ;
                                        break;
                                    case 2 :
                                        $message = "Your performance rating is required before the contractor can be paid.";
                                        break;
                                    case 3 :
                                        $message = "Please continue to apply, and we hope to find another attractive opportunity for you soon.";
                                        break;
                                    case 4 :
                                        $message = "Please log in to your mobile app to review the interview request and select your preferred time.";
                                        break;
                                    case 5 :
                                        $message = "Please log in to your mobile app to review the interview request and select your preferred time.<br/><br/>We are here to assist if you need us.";
                                        break;
                                    case 12 :
                                        $message = "Once you have validated your email address (required), your PeopleCaddie account will be activated." ;
                                        break;
                                    case 13 :
                                        $message = "We look forward to working with you to fulfill your contracting needs." ;
                                        break;
                                }
                                echo $message;
                            ?>
                            
                        </h5>
                        <h1 style="color:#000; font-size:16px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 12px; margin:0px; ">PeopleCaddie.com</h1>
                        <p style="color:#666666; font-size:12px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px; margin:0px;  font-weight:normal;">This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
