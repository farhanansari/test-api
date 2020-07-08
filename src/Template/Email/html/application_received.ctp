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
                  echo $this->element('PCEmail/header',['subject' => 'Application Received']);
                  ?>
                </tr>
               		<tr>
			<td style="padding:7px 0px 12px;" >
			<h6 style="padding:0px 0px 14px; margin:0px; color:#3054ba; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:bold;">
			<?= $var['title']; ?></h6>
			<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; ">We have received your application for the following assignment:</p>
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
				<span style="color:#2f5496;">Job ID #</span> <?= $var['joborder_id']; ?></p>
				</td>

				<td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;text-align: right;">
                                <span style="color:#2f5496;">Job Title #</span> <?= $var['joborder_title']; ?></p>
				</td>
				</tr>
				<tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:0px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
				<span style="color:#2f5496;">Company Name #</span> <?= $var['employer']; ?></p>
				</td>

				<td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:0px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;text-align: right;">
                                <span style="color:#2f5496;">Company Location #</span> <?= $var['location'] ?></p>
				</td>
				</tr>
				<tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:0px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
				<span style="color:#2f5496;">Start Date #</span> <?= $date=isset($var['startDate']) ? gmdate('D M d,Y', $var['startDate']) : ""; ?></p>
				</td>

				<td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:0px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;text-align: right;">
				<span style="color:#2f5496;">Duration (in weeks) #</span> <?= $var['durationWeeks']; ?></p></p>
				</td>
				</tr>
				<tr>
				<td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:0px 0px 10px; ">
				<p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
				<span style="color:#2f5496;">Hourly Pay Rate Range:</span> <?= '$'.$var['minHourlyRate'].' - $'.$var['maxHourlyRate'].' per hour'; ?></p>
				</td>

				<td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:0px 0px 10px; ">
				<p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;"></p>
				</td>
				</tr>
				</tbody>
			</table>
			</td>
		</tr>
		<tr>
			<td style="padding:7px 0px 12px;" >
			<p style="padding:0px; margin:0px; color:#000000; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; ">We will notify you immediately as soon as we know if the employer is interested in moving forward with your candidacy.</p>
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
