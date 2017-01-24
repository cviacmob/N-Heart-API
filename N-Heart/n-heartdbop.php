<?php
require 'vendor/autoload.php';

require 'mysql.php';

include ('lib/Way2SMS/way2sms-api.php');

$app = new Slim\App();
$app->post('/invitation',
function ($request, $response, $args)
	{
	invitation($request->getParsedBody());
	});
$app->get('/check', 'checkinvitationstatus');
$app->post('/updateinvitation',
function ($request, $response, $args)
	{
	updateinvite($request->getParsedBody());
	});

// $app->get('/getinvitation','getinvitation');

$app->get('/getinvitation/{mobile}',
function ($request, $response, $args)
	{
	getinvitation($args['mobile']);
	});
$app->run();

function checkmobile($mobile)
	{
	$db = connect_db();
	$sql = "SELECT * FROM invitation where mobile='$mobile' and status='paired'";
	$exe = $db->query($sql);
	$db = null;
	if ($exe->num_rows > 0)
		{
		return true;
		}

	return false;
	}

function checkToMobile($To_mobile)
	{
	$db = connect_db();
	$sql = "SELECT status FROM invitation where to_mobile='$To_mobile'OR mobile='$To_mobile'";
	$exe = $db->query($sql);
		$data = $exe->fetch_all(MYSQLI_ASSOC);
	$db = null;
		foreach($data as $status){
		if(in_array("unpaired",$status)){
			return 1;

		}
	else if(in_array("paired",$status)){
			return 2;

		}
	}
	
			return 0;
		}

function checkTomobileStatus($To_mobile)
	{
	$db = connect_db();
	$sql = "select * from invitation where to_mobile='$To_mobile' and status='paired'";
	$exe = $db->query($sql);
	if ($exe->num_rows > 0)
		{
		return true;
		}

	return false;
	}

function validate_email($email)
	{
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
			return;
			}
	else if (!preg_match('/^\+?([0-9]{1,4})\)?[-. ]?([0-9]{9})$/', $to_mobile))
			{
			$result['code'] = 1001;
			$result['desc'] = "Mobile number not valid";
			echo json_encode($result);
			return;
			}

	else if (validate_email($email))
			{
			$result['code'] = 1002;
			$result['desc'] = "email not valid";
			echo json_encode($result);
			return;
			}
		
		
	
	}
	function checkHistory($To_mobile)
		{
		$db = connect_db();
		$sql = "SELECT * FROM history_invitation where to_mobile='$To_mobile' and status='pending'";
		$exe = $db->query($sql);
		$data = $exe->fetch_array(MYSQLI_ASSOC);

		// $	sa=$exe->fetch_field();
		// 	if($sa->length==$sa->max_length)

		if ($exe->num_rows > 0)
			{
			return true;
			}

		return false;
		}

	function invitation($data)
		{
		$mobile = $data['mobile'];
		$To_mobile = $data['to_mobile'];
		$result = array();
		$msg = "hi";
		$db = connect_db();
		if (validation($data))
			{
            return;
			}
			else if (checkToMobile($To_mobile)==0||checkToMobile($To_mobile)==1)
				{
				if (checkHistory($To_mobile))
					{
					$result['code'] = 1003;
					$result['desc'] = "Invitation pending";
					echo json_encode($result);
					return;
					}
				  else
					{
					$db = connect_db();
					$status = "unpaired";
					$sql = "insert into invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status')";
					$exe = $db->query($sql);
					$db = null;
					$db = connect_db();
					$status1= "pending";
					$sql = "insert into history_invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status1')";
					$exe = $db->query($sql);
					$db = null;

					//					$res=sendWay2SMS('7904446431','mob1234', $mobile, $msg);

					$result['code'] = 0;
					$result['desc'] = "Success";
					echo json_encode($result);
					}
				}
			  /*else

				{
				$result = array();
				$msg = "hi";
				$db = connect_db();
				$status = "unpaired";
				$sql = "insert into invitation(mobile,email_id,name,to_mobile,status)values('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status')";
				$exe = $db->query($sql);
				$db = null;
				if ($exe == true)
					{
					$db = connect_db();
					$status1 = "pending";
					$sql = "insert into history_invitation (mobile,email_id,name,to_mobile,status) values ('$data[mobile]','$data[email]','$data[name]','$To_mobile','$status1')";
					$exe = $db->query($sql);
					$db = null;

					// 			$res=sendWay2SMS('7904446431','mob1234', $To_mobile, $msg);

					$result['code'] = 0;
					$result['desc'] = "Success";
					echo json_encode($result);
					}
				}*/
				else{
					$result['code'] = 1004;
					$result['desc'] = "Already paired";
					echo json_encode($result);
				}
			
		}

	function checkinvitationstatus($data)
		{
		$db = connect_db();
		$sql = "select status from invitation where mobile='$data[mobile]' and to_mobile='$data[to_mobile]' and status='paired'";
		$exe = $db->query($sql);
		$dat = $exe->fetch_all(MYSQLI_ASSOC);
		$db = null;
		if ($exe->num_rows > 0)
			{
			return true;
			}

		return false;
		}

	function checkhistoryinvitation($data)
		{
		$db = connect_db();
		$sql = "select status from history_invitation where mobile='$data[mobile]' and to_mobile='$data[to_mobile]'";
		$exe = $db->query($sql);
		$dat = $exe->fetch_all(MYSQLI_ASSOC);
		$db = null;
		if ($exe->num_rows > 0)
		{
	/*	if ((!empty($dat['status'])) == "rejected")
			{
			return 1;
			}
		  else
		if ((!empty($dat[1])) == "accept")
			{
			return 2;
			}
		
	*/	
	foreach($dat as $status){
		if(in_array("rejected",$status)){
			return 1;

		}
	else if(in_array("accept",$status)){
			return 2;

		}
	}
	
			return 0;
		}


	}

	function updateinvite($data)
		{
		$db = connect_db();
		$result = array();
		$today = date("Y-m-d H:i:s");
		if($data['status']=="accept"){
		if (!checkinvitationstatus($data))
			{
			if (checkhistoryinvitation($data)==0)
				{
				$status = "paired";
				$sql = "update invitation set status='$status',paired_date='$today' where mobile='$data[mobile]' and to_mobile='$data[to_mobile]'";
				$exe = $db->query($sql);
				$db = null;
				if ($exe == true)
					{
					$msg = "accepted your request";
					$db = connect_db();
					$status1 = "accept";
					$sql = "update history_invitation set status='$status1',history_date='$today' where mobile='$data[mobile]' and to_mobile='$data[to_mobile]'";
					$exe = $db->query($sql);
					$db = null;

					// 				$res=sendWay2SMS('7904446431','mob1234', $data['to_mobile'], $msg);

					$result['code'] = 0;
					$result['desc'] = "Success";
					echo json_encode($result);
					return;
				
					}
				}
			}
					
		  else if ((checkhistoryinvitation($data))==1)
			{
					$status = "paired";
				    $sql = "update invitation set status='$status',paired_date='$today' where mobile='$data[mobile]' and to_mobile='$data[to_mobile]'";
				    $exe = $db->query($sql);
			     	$db = null;
				if ($exe == true)
					{
					$msg = "accepted your request";
					$db = connect_db();
					$status1 = "accept";
					$sql = "update history_invitation set status='$status1',history_date='$today' where mobile='$data[mobile]' and to_mobile='$data[to_mobile]'";
					$exe = $db->query($sql);
					$db = null;

					// 				$res=sendWay2SMS('7904446431','mob1234', $data['to_mobile'], $msg);

					$result['code'] = 0;
					$result['desc'] = "Success";
					echo json_encode($result);
					return;
		
			}

		
		}
		  else 
				{
				$result['code'] = 1004;
				$result['desc'] = "Already paired and accepted by the user or invitation is pending";
				echo json_encode($result);
				return;
				}
		}
		else{
			        $db = connect_db();
					$status1 = "rejected";
					$sql = "update history_invitation set status='$status1',history_date='$today' where mobile='$data[mobile]' and to_mobile='$data[to_mobile]'";
					$exe = $db->query($sql);
					$db = null;
					$result['code'] = 1005;
				    $result['desc'] = "Rejected By the user";
				    echo json_encode($result);
				    return;

		}
		
		}
	function getinvitation($mobile)
		{
		$db = connect_db();
		$result=array();
		$sql = "select to_mobile,name,email_id from invitation where to_mobile='$mobile'";
		$exe = $db->query($sql);
		$db = null;
		$dat = $exe->fetch_all(MYSQLI_ASSOC);
		echo json_encode($dat);
		}

?>
