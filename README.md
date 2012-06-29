<h2>EBS Hostbill</h2>
===========

EBS Hostbill is a free, open source Payment Gateway Module for Hostbill Billing Application, developed in PHP released under <a href="https://github.com/nitishdhar/ebs-hostbill/blob/master/License.txt">MIT License</a>. 

<h2>Setup</h2>

Just upload these files to the hostbill-installation-directory/includes/modules/Payment

Login to Admin Panel on Hostbill & activate the payment module from Plugins section. Provide the required details for the module to work.

<strong>Note-</strong> Please make sure your hosting provider allows get query string length upto a minimum of 1024. This is required to fetch the response string after payment is authorized.

In case your hosting does not allow this, the module will still take the payment but it will fail to add the transaction to hostbill & invoice will stay unpaid. You can manually mark it paid in this case.
