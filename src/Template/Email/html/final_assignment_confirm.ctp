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
                  echo $this->element('PCEmail/header',['subject' => 'Assignment Confirmed']);
                  ?>
                </tr>
                <tr>
                    <td style="padding:7px 0px 12px;" >
                        <h6 style="padding:0px 0px 14px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">
                            <?= $var['title']; ?></h6>
                        <p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; "><?= $var['message']; ?></p>
                    </td>
                </tr>
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
                                    <span style="color:#2f5496;">Job ID #</span> <?php echo $jobID = isset($var['assignmentData']['joborder_id']) ? $var['assignmentData']['joborder_id']: 'NIL' ;?>
                                </p>
				</td>

				<td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Title #</span> <?php echo $jobTitle = isset($var['jobTitle']) ? $var['jobTitle'] : 'NIL' ;?>
                                </p>
				</td>
				</tr>
                                <?php if(isset($var['statusFlag']) && ($var['statusFlag'] == 9)) { ?>    
                                <tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Company #</span> <?php echo $employer = isset($var['employer']) ? $var['employer'] : 'NIL' ;?>
                                </p>
				</td>
                                <td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Address #</span> 
                                    <?php 
                                    if(isset($var['assignmentData']) && !empty($var['assignmentData'])){
                                        
                                    $address = $var['assignmentData']['company_address']['address2'];
                                    $city = $var['assignmentData']['company_address']['city'];
                                    $state = $var['assignmentData']['company_address']['state'];
                                    $countryName = $var['assignmentData']['company_address']['countryName'];
                                    $zipCode = $var['assignmentData']['company_address']['zip'];
                                    
                                    echo $location = $address.', '.$city.', '.$state.', '.trim($countryName).', '.$zipCode;
                                    } else {
                                        echo 'NIL' ;
                                    }
                                    ?>
                                </p>
				</td>
				</tr>
                                <?php } ?>
                                <?php if(isset($var['statusFlag']) && ($var['statusFlag'] == 10)) { ?>
                                <tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Selected Contractor #</span> <?php echo $contractorName = isset($var['contractorName']) ? $var['contractorName'] : 'NIL' ; ?>
                                </p>
				</td>
				</tr>
                                <?php } ?>
                                <?php if(isset($var['statusFlag']) && ($var['statusFlag'] == 11)) { ?>
                                <tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Company #</span> <?php echo $employer = isset($var['employer']) ? $var['employer'] : 'NIL' ;?>
                                </p>
				</td>
                                </tr>
                                <tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Selected Contractor #</span> <?php echo $contractorName = isset($var['contractorName']) ? $var['contractorName'] : 'NIL' ; ?>
                                </p>
				</td>
                                <td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Address #</span>
                                    <?php 
                                    if(isset($var['assignmentData']) && !empty($var['assignmentData'])) {
                                        
                                    $address = $var['assignmentData']['company_address']['address2'];
                                    $city = $var['assignmentData']['company_address']['city'];
                                    $state = $var['assignmentData']['company_address']['state'];
                                    $countryName = $var['assignmentData']['company_address']['countryName'];
                                    $zipCode = $var['assignmentData']['company_address']['zip'];
                                    
                                    echo $location = $address.', '.$city.', '.$state.', '.trim($countryName).', '.$zipCode;
                                    } else {
                                        echo 'NIL' ;
                                    }
                                    ?>
                                </p>
				</td>
				</tr>
                                <?php } ?>
                                <tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Start Date #</span> <?= date('D M d,Y', $var['startDate']); ?>
                                </p>
				</td>
                                <td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Duration (Weeks) #</span> <?php echo $durationWeeks = isset($var['durationWeeks']) ? $var['durationWeeks'] : 'NIL' ;?>
                                </p>
				</td>
				</tr>
                                <tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <?php if((isset($var['statusFlag'])) && ($var['statusFlag'] == 9)) { ?>
                                    <span style="color:#2f5496;">Hourly Pay Rate #</span> 
                                    <?php echo '$'.$var['assignmentData']['candidate_bid_value'].' / hour'; } else { ?>
                                    <span style="color:#2f5496;">Hourly Billing Rate #</span> 
                                    <?php echo '$'.$var['billRate'].' / hour'; }  ?> 
                                </p>
				</td>
                                <?php if((isset($var['statusFlag'])) && ($var['statusFlag'] == 9)) { ?>
                                <td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                    <span style="color:#2f5496;">Hiring Manager Name #</span> <?php echo $hiringManagerName = isset($var['assignmentData']['hiring_manager']) ? ucwords($var['assignmentData']['hiring_manager']) : 'NIL' ;?>
                                </p>
				</td>
                                <?php } ?>
				</tr>
                                    <?php if(isset($var['statusFlag']) && ($var['statusFlag'] == 9 )) { ?>
                                    <tr>
                                        <td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
                                            <p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                                    <span style="color:#2f5496;">Hiring Manager Phone No #</span> <?php echo $hiringManagerPhone = isset($var['assignmentData']['client_phone']) ? $var['assignmentData']['client_phone'] : 'NIL' ;?>
                                            </p>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <?php if(isset($var['statusFlag']) && ($var['statusFlag'] == 10 )) { ?>
                                    <tr>
                                        <td>
                                            <table width="100%">
                                                <tbody>
                                                    <tr>
                                                        <td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size: 16px; margin: 0;  padding:4px 0px 10px; ">
                                                            <a href="<?= $var['verifyLink']; ?>" style="border-radius: 6px; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;  background-color:#4472c4; padding:6px 45px; border:1px solid #2d4570; color:#000; cursor:pointer; outline:none; font-weight:bold;text-decoration: none;">View Position</a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <?php }?>
				</tbody>
			</table>
		    </td>
		</tr>
                <tr>
                    <td style="padding:7px 0px 30px;">
                        <h5 style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 13px; margin:0px;  font-weight:normal;">
                            <?php
                            if($var['statusFlag'] == 9 ) {
                                echo "Please log in to your mobile app for additional information. We are here to assist if you need us.";
                            } else if($var['statusFlag'] == 10 ) {
                                echo "We are here to assist if you need us.";
                            } else {
                                echo "";
                            }
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
