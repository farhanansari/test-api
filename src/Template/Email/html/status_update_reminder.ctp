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
                <tr>
                    <td style="padding:7px 0px 30px;">
                        <h5 style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 13px; margin:0px;  font-weight:normal;">
                            Please log in to your mobile app to confirm/update your status. Hopefully, we will be able to find a match for you soon.
                        </h5>
                        <h1 style="color:#000; font-size:16px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 12px; margin:0px; ">PeopleCaddie.com</h1>
                        <p style="color:#666666; font-size:12px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px; margin:0px;  font-weight:normal;">This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
