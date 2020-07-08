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
                  echo $this->element('PCEmail/header',['subject' => 'Admin Email Not Validated']);
                  ?>
                </tr>
           <tr>
				<td style="padding:7px 0px 12px;" >
				<h6 style="padding:0px 0px 14px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">
				<?= isset($var['fullName'])?$var['fullName']:"Hello"; ?>,</h6>
				<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; ">A new registration request has been received, but the user has not validated her/his email address. Please click below to view the registration request and determine appropriate next steps:</p>
				</td>
			</tr>
			<tr>
			<td>
			<table width="100%">
				<tbody>
				<tr>
					<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size: 16px; margin: 0;  padding:4px 0px 10px; ">
					<a href="<?php echo WEB_SERVER_ADDR; ?>" target="_blank" style="border-radius: 6px; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;  background-color:#4472c4; padding:6px 45px; border:1px solid #2d4570; color:#000; cursor:pointer; outline:none; font-weight:bold;" >View Registration</a>
					</td>
				</tr>
				</tbody>
			</table>
			</td>
		</tr>
		<tr> 
			<td style="padding:10px 0px 0px;">
			<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; ">Thank you.</p>
			</td>
		</tr>
		<tr>
                    <?php 
                      echo $this->element('PCEmail/footer');
                      ?>
		</tr>
            </tbody>
        </table>
    </body>
</html>
