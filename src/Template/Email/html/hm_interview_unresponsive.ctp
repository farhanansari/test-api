<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
<head>
<meta name="viewport" content="width=device-width" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>PeopleCaddie :: Mailer</title>
</head>
<body style="bgcolor:#f6f6f6">					
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
			<?php echo $var['title'] ?></h6>
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
                                            <span style="color:#2f5496;">Job ID #</span>
                                 <?php echo $placementID = isset($var['placementID']) ? $var['placementID']: 'NIL' ;?></p>
                                    </td>

                                    <td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
                                        <p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                            <span style="color:#2f5496;">Job Title #</span>
                                    <?php echo $jobTitle = isset($var['jobTitle']) ? $var['jobTitle'] : 'NIL' ;?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0; padding:10px 0px 10px; ">
                                        <p style="color:#000; font-size:14px; padding:0px; margin:0px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;">
                                        <span style="color:#2f5496;">Interviewee #</span>
                                        <?php
                                        echo $firstName=(isset($var['intw_data']['placement_contractor_fname']) && !empty($var['intw_data']['placement_contractor_fname'])) ? $var['intw_data']['placement_contractor_fname'] : "";
                                        echo $firstName=(isset($var['intw_data']['placement_contractor_lname']) && !empty($var['intw_data']['placement_contractor_lname'])) ? " ".$var['intw_data']['placement_contractor_lname'] : "";
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td align="left" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size: 16px; margin: 0;  padding:4px 0px 10px; ">
                                        <a href="<?= $var['jobLink']; ?>" style="border-radius: 6px; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;  background-color:#4472c4; padding:6px 45px; border:1px solid #2d4570; color:#000; cursor:pointer; outline:none; font-weight:bold;text-decoration: none;">View Position</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:7px 0px 30px;">
                        <p style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 12px; margin:0px; ">Please follow-up as required.</p>
                        <h1 style="color:#000; font-size:16px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 12px; margin:0px; ">PeopleCaddie.com</h1>
                        <p style="color:#666666; font-size:12px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px; margin:0px;  font-weight:normal;">This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
