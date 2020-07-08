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
                                 <?php echo $placementID = isset($var['joborder_id']) ? $var['joborder_id']: 'NIL' ;?></p>
                                    </td>

                                    <td align="right" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; margin: 0;  padding:10px 0px 10px; ">
                                        <p style="color:#000; font-size:14px;  padding:0px; margin:0px;  font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:normal;text-align:right;">
                                            <span style="color:#2f5496;">Job Title #</span>
                                    <?php echo $jobTitle = isset($var['jobTitle']) ? $var['jobTitle'] : 'NIL' ;?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <?php if(isset($var['statusFlag']) && ($var['statusFlag'] == 2)) { ?>
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
                <?php } ?>
                <tr>
                    <td style="padding:7px 0px 30px;">
                        <h5 style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 13px; margin:0px;  font-weight:normal;">
                            <span style="color:#2f5496;">When:</span> 
                        <?php
                            if(isset($var['intw_data']['dateBegin']) && !is_null($var['intw_data']['dateBegin'])){
                                echo date('D M d,Y h:i A',$var['intw_data']['dateBegin']);
                            }
                            
                            if(isset($var['intw_data']['dateEnd']) && !is_null($var['intw_data']['dateEnd'])){
                                 echo " - ".date('h:i A',$var['intw_data']['dateEnd']). " UTC";
                            }
                        ?>
                            <!--  [Day of Week] [Month] [Date], [Year] [Start] – [End] [Time Zone]-->

                        </h5>
                        <h5 style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 13px; margin:0px;  font-weight:normal;">
                            <span style="color:#2f5496;">Who:</span>
                        <?php
                        echo $Name=(isset($var['intw_data']['hiring_manager']) && !empty($var['intw_data']['hiring_manager'])) ? $var['intw_data']['hiring_manager'] : "";
                        
                        ?>
                         -  <?php  echo $companyName=(isset($var['intw_data']['employer']) && !empty($var['intw_data']['employer'])) ? $var['intw_data']['employer'] : ""; ?>
                        </h5>
                        <h5 style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 20px; margin:0px;  font-weight:normal;">
                            <span style="color:#2f5496;">Message:</span>
                        
                        <?php 
                        if($var['intw_data']['communication_type'] == 'Phone interview') { ?>
                        Hiring manager to call you at
                        <?php 
                        $phoneNo=(isset($var['phone_no']) && !empty($var['phone_no'])) ? $var['phone_no'] : "";
                        if(!empty($phoneNo)) {
                            echo preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $phoneNo);
                        }
                            
                        } elseif($var['intw_data']['communication_type'] == 'Skype Interview' ) {
                            
                            if(!empty($var['intw_data']['placement_contractor_skype'])) { ?>
                        
                                Hiring manager to contact you at <?php 
                                echo $skype=(isset($var['intw_data']['placement_contractor_skype']) && !empty($var['intw_data']['placement_contractor_skype'])) ? $var['intw_data']['placement_contractor_skype'] : "";
                            } else { ?>
                        
                            Hiring manager to contact you at <b>[please update your profile with SkypeID]</b>  
                            <?php 
                            }
                            
                        } else {
                            ?>
                            Hiring manager to meet you at 
                            <?php 
                            if(isset($var['intw_data']['company_address']) && !empty($var['intw_data']['company_address'])) {
                                echo $address = $var['intw_data']['company_address']['address2'].', '. $var['intw_data']['company_address']['city'].', '.$var['intw_data']['company_address']['state'].', '.$var['intw_data']['company_address']['countryName'].', '.$var['intw_data']['company_address']['zip'];
                            }
                        }
                        ?>
                        
                        </h5>
                        <?php if(isset($var['statusFlag']) && ($var['statusFlag'] == 1)) { ?>
                        <h5 style="color:#000; font-size:14px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 20px; margin:0px;  font-weight:normal;">We are here to assist if you need us.</h5>
                        <?php } ?>
                        <h1 style="color:#000; font-size:16px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px 0px 12px; margin:0px; ">PeopleCaddie.com</h1>
                        <p style="color:#666666; font-size:12px; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding:0px; margin:0px;  font-weight:normal;">This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
