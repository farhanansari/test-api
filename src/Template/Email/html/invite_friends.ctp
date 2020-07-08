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
                  echo $this->element('PCEmail/header',['subject' => 'Invitation']);
                  ?>
                </tr>
		<tr>
			<td style="padding:7px 0px 7px;" >
			<h6 style="padding:0px 0px 12px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">
			Hi,</h6>
			<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; "><?= $var['name']; ?> invited you to join PeopleCaddie.com, the first online marketplace in the United States that is exclusively focused on temporary, on-site employment!</p>
			</td>
		</tr>
		<tr>
			<td style="padding:7px 0px 7px;" >
			<h6 style="padding:0px 0px 12px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">
			Why Should I Join PeopleCaddie?</h6>
			<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; ">Through our mobile-optimized platform, we help contractors and employers find each other in a fraction of the time required by traditional staffing firms.</p>
			</td>
		</tr>
		<tr>
			<td style="padding:7px 0px 7px;" >
			<h6 style="padding:0px 0px 12px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">
			Where do I Sign Up?</h6>
			<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; ">To sign up or find out more, please click on the link below to go to PeopleCaddie.com or download our free mobile app from the AppStore (iOS) or Google Play (Android):</p>
			</td>
		</tr>
		<tr>
			<td>
			<table width="100%">
				<tbody>
				<tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size: 16px; margin: 0;  padding:4px 0px 10px; ">
				<?php 
					$siteurl = WEB_SERVER_ADDR_MARKETING; //'http://www.google.com';
					//$siteurl = preg_replace('#^https?://#', '', $siteurl);
					//echo $siteurl; // www.google.com
				?>
				<a href="<?php echo $siteurl; ?>" target="_blank" style="border-radius: 6px; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;  background-color:#4472c4; padding:6px 45px; border:1px solid #2d4570; color:#000; cursor:pointer; outline:none; font-weight:bold;" >PeopleCaddie.com</a>
				</td>
				</tr>
				</tbody>
			</table>
			<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; ">Be a part of the revolution!</p>
			</td>
		</tr>
		<tr>
			<td style="padding:20px 0px 30px;">
			<h1 style="color:#3054ba; font-size:16px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 3px; margin:0px; ">PeopleCaddie.com</h1>
			<p style="color:#666666; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding-bottom:10px; margin:0px;  font-weight:normal; font-style:italic;">“Your Mobile Staffing Agency”</p>
			<p style="color:#666666; font-size:12px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px; margin:0px;  font-weight:normal;">This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
			</td>
		</tr>
            </tbody>
        </table>
    </body>
</html>
