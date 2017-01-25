<?php
require 'vendor/autoload.php';

require 'mysql.php';

include ('lib/Way2SMS/way2sms-api.php');


$app = new Slim\App();


$app->post('/sendInvite',
function ($request, $response, $args)
	{
	sendInvite($request->getParsedBody());
	});


$app->get('/checkInvite/{id}', 
function ($request,$response,$args) {
	checkInvite($args['id']);
});

$app->post('/updateInvite',
function ($request, $response, $args)
	{
	updateInvite($request->getParsedBody());
	});

$app->get('/getInvite/{mobile}',
function ($request, $response, $args)
	{
	getInvite($args['mobile']);
	});
$app->run();

function sendInvite($data) {
    $mobile = $data['mobile'];
	$To_mobile = $data['to_mobile'];
    $result = array();
    $db = connect_db();
    if (validation($data)  == false) {
        return;
    }

    $retval = checkToMobile($mobile,$To_mobile);
    if ($retval == 0) {
        //not found
        $status = "unpaired";
		$sql = "insert into invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status')";
		$exe = $db->query($sql);
		$last_id = $db->insert_id;
        $status1= "pending";
		$sql = "insert into history_invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status1')";
		$exe = $db->query($sql);
		$result['code'] = 0;
		$result['id'] = $last_id;
		$result['desc'] = "Success";
		echo json_encode($result);
		return;
    }

    if ($retval == 2) {
        //paired
        $result['code'] = 1004;
		$result['desc'] = "Already paired";
		echo json_encode($result);
        $status1= "Already Paired";
		$sql = "insert into history_invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status1')";
		$exe = $db->query($sql);
        return;
    }

    if ($retval == 1) {
        // unpaired (pending)
        $result['code'] = 1003;
        $result['desc'] = "Invitation pending";
        echo json_encode($result);
        $status1= "Already Invited";
		$sql = "insert into history_invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status1')";
		$exe = $db->query($sql);
        return;
    }

    if ($retval == 3) {
        // rejected
        $result['code'] = 1007;
		$result['desc'] = "Invitation Rejected";
		echo json_encode($result);
        $status1= "Already Rejected";
		$sql = "insert into history_invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status1')";
		$exe = $db->query($sql);
        return;
    }
}

function checkToMobile($mobile, $tomobile)	{
	$db = connect_db();
	$sql = "SELECT status, to_mobile FROM invitation where mobile='$mobile'";
	$exe = $db->query($sql);
	$data = $exe->fetch_all(MYSQLI_ASSOC);
	$db = null;
	foreach($data as $record){		
		$status = $record["status"];
		$dmobile = $record["to_mobile"];
		if ($status == "unpaired"){
			return 1;
		}
	    else if ($status == "paired") {
			return 2;
		}
        else if ($status == "rejected") {
			if ($tomobile == $dmobile) {
				return 3;
			}
		}
	}	
	return 0;
}

function validate_email($email)	{
	if (!filter_var($email, FILTER_VALIDATE_EMAIL))
	{
		return true;
	}
	return false;
}

function validation($data)
	{
	$mobile = $data['mobile'];
	$to_mobile = $data['to_mobile'];
	$email = $data['email'];
	$result = array();
	if (!preg_match('/^\+?([0-9]{1,4})\)?[-. ]?([0-9]{9})$/', $mobile))
		{
			$result['code'] = 1001;
			$result['desc'] = "Mobile number not valid";
			echo json_encode($result);
			return false;
			}
	else if (!preg_match('/^\+?([0-9]{1,4})\)?[-. ]?([0-9]{9})$/', $to_mobile))
			{
			$result['code'] = 1001;
			$result['desc'] = "Mobile number not valid";
			echo json_encode($result);
			return false;
			}

	else if (validate_email($email))
			{
			$result['code'] = 1002;
			$result['desc'] = "email not valid";
			echo json_encode($result);
			return false;
			}	
    return true;
	}

function checkInvite($id)  {
	$result = array();
    $db = connect_db();
	$sql = "SELECT status FROM invitation where invtId='$id'";
	$exe = $db->query($sql);
	$data = $exe->fetch_all(MYSQLI_ASSOC);	
	foreach($data as $record){		
		$status = $record["status"];
		$result['code'] = 0;
		$result['desc'] = "sucess";
		$result['status'] = $status;
		echo json_encode($result);
		return;
	}
	$result['code'] = 1008;
	$result['desc'] = "Invite Id not found";
	echo json_encode($result);
	return;
}

function updateInvite($data) {
    $status = $data['status'];
	if ($status == "accepted") {
		$status = "paired";
	}
	else if ($status != "rejected") {
		//send error
		$result['code'] = 1010;
		$result['desc'] = "Invailid status";
		echo json_encode($result);
		return;
	}

	$db = connect_db();
	$today = date("Y-m-d H:i:s");
	$sql = "update invitation set status='$status',paired_date='$today' where mobile='$data[mobile]' and to_mobile='$data[to_mobile]'";
	$exe = $db->query($sql);
	$last_id=$db->affected_rows;
	if ($last_id > 0){
		//success
		$result['code'] = 0;
		$result['desc'] = "update sucess";
		echo json_encode($result);
		return;
	}
	// not found
	$result['code'] = 1009;
	$result['desc'] = "update failed";
	echo json_encode($result);
	return;
}

function getInvite($mobile) {
	$result = array();
    $db = connect_db();
	$sql = "SELECT name,mobile,email_id,status FROM invitation where to_mobile='$mobile'";
	$exe = $db->query($sql);
	$data = $exe->fetch_all(MYSQLI_ASSOC);	
	foreach($data as $record){		
		$status = $record["status"];
		if ($status == "unpaired") {
			$result[0] = $record;
			echo json_encode($result);
			return;
		}		
	}
	echo json_encode($result);
	return;
}
