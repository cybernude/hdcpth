<?php
session_start();

if(!isset($_SESSION['user_session']))
{
	header("Location: index.php");
}

include('include/config.inc.php') ;
//require_once("function.php");
//$obj=new ConnDB();
$op = $_GET['op'] ;
//$_GET['vn'] = 554491 ;
switch($op){

case "showinfo":
	//$sql = "select pt.pop_id,pt.fname,pt.lname,if(pt.male =1, 'ชาย','หญิง') as sex,round(DATEDIFF(CURDATE(),pt.brthdate) / 365.25) as age
	// from pt where pt.pop_id = ".$_GET['pop_id'] ;
	 $stmt = mysqli_prepare($conn,'select t.cid,t.name,t.lname,if(t.sex =1, "ชาย","หญิง") as sex,round(DATEDIFF(CURDATE(),t.birth) / 365.25) as age
 	 from t_person_cid t where t.cid = ?') ;
	 mysqli_stmt_bind_param($stmt,"i",$_GET['pop_id']);
	 mysqli_stmt_execute($stmt);
	 mysqli_stmt_bind_result($stmt,$cid,$name,$lname,$sex,$age);
	 mysqli_stmt_fetch($stmt);
//	 $query = mysqli_query($conn,$sql);
//	 $arr = mysqli_fetch_array($query) ;

	 echo "<div class='active step'>" ;
	 echo "<i class='payment icon'></i>";
	 echo "<div class='content'>";
	 echo "<div class='title'>ประวัติการรับบริการ</div>" ;
	 echo "<div class='description'> ชื่อ :: ".$name."  ".$lname." เพศ " .$sex." อายุ ".$age." ปี</div>" ;
	 echo "</div>";
	 echo "</div>";
?>
<iframe onload="showVisit(<?php echo $cid; ?>)" frameborder='0' width='0' height='0'></iframe>
<?php

break;
?>

<?php
case "visit":
//$sql = "select ovst.vn,ovst.vstdttm
//from ovst inner join ovstdx on ovst.vn = ovstdx.vn
//inner join pt on ovst.hn = pt.hn
//where pt.pop_id = ".$_GET['pop_id']."
//group by ovst.vn order by ovst.vstdttm desc"  ;
//where pt.pop_id = ".$_GET['pop_id']."
//$query = $obj->getFetch($sql) ;

$stmt = mysqli_prepare($conn,'select s.hospcode,s.hn,s.date_serv,s.time_serv,s.seq,s.chiefcomp,concat(s.hospcode,",",s.pid,",",s.seq) as servicex,s.servplace
from service s inner join person t on s.hospcode = t.hospcode and s.pid = t.pid
where t.cid = ? order by s.date_serv desc');

mysqli_stmt_bind_param($stmt,"i",$_GET['cid']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$hospcode,$hn,$date_serv,$time_serv,$seq,$chiefcomp,$servicex,$serviceplace);

//$query = mysqli_query($conn,$sql);
?>
<select name="visitdate" multiple="multiple" style="width:150px;height:100%;overflow-y:hidden" onchange="showDiag(this.value)">
<?php
//while($arr=$query->fetch_object()){
	while(mysqli_stmt_fetch($stmt)){
?>
		<option value="<?php echo $servicex ; ?>"><?php echo $hospcode.' - '.$date_serv.' - '.$time_serv ; ?></option>
<?php
	}
echo "</select>" ;
?>
<?php
break;

case "diag":
//create log with what user see patient visit
function GetClientMac(){
    $macAddr=false;
    $arp=`arp -n`;
    $lines=explode("\n", $arp);

    foreach($lines as $line){
        $cols=preg_split('/\s+/', trim($line));

        if ($cols[0]==$_SERVER['REMOTE_ADDR']){
            $macAddr=$cols[2];
        }
    }

    return $macAddr;
}
/*
$user_id = $_SESSION['user_session'] ;
$user_addr = $_SERVER["REMOTE_ADDR"];
$user_agent = $_SERVER["HTTP_USER_AGENT"];
$user_mac = GetClientMac();
//$viewdate =

$stmt = mysqli_prepare($conn,'insert into view_pt_log (user_id,viewdate,vn,user_addr,user_agent,user_mac) values (?,NOW(),?,?,?,?)') ;
mysqli_stmt_bind_param($stmt,'iisss',$user_id,$_GET['vn'],$user_addr,$user_agent,$user_mac);
mysqli_stmt_execute($stmt);
*/

$stmt = mysqli_prepare($conn,"select dxo.diagcode,dxo.diagtype,concat(s.hospcode,'|',s.pid,'|',s.seq) as servicex
from diagnosis_opd dxo inner join service s on dxo.hospcode = s.hospcode and dxo.pid = s.pid and dxo.seq = s.seq
where s.hospcode = ? and s.pid = ?  and s.seq = ? order by diagtype");
//$sql = "select * from ovstdx where ovstdx.vn = ".$_GET['vn'] ;
$hps = explode(',',$_GET['servicex']);
$testx = $_GET['servicex'];
echo $_GET['servicex']; echo "<br/>";
echo $hps[0]."-".$hps[1]."-".$hps[2];echo "<br/>";
mysqli_stmt_bind_param($stmt,"iii",$hps[0],$hps[1],$hps[2]);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$diagcode,$diagtype,$servicex);
//mysqli_stmt_fetch($stmt);

//$query = mysqli_query($conn,$sql) or die(mysql_error()) ;
while(mysqli_stmt_fetch($stmt)){
	echo $diagtype.' - '.$diagcode."<br>" ;
}
echo $servicex;
?>

<iframe onload="showRx(<?php echo $servicex; ?>)" frameborder='0' width='0' height='0'></iframe>
<?php
//echo "Hello World" ;
break;

case "rx":
	//$sql = "select * from prscdt inner join prsc on prsc.prscno = prscdt.prscno where prsc.vn = ".$_GET['vn'] ;
	$stmt = mysqli_prepare($conn,'select rxo.dname,rxo.amount,rxo.unit
from drug_opd rxo inner join service s
on rxo.hospcode = s.hospcode
and rxo.pid = s.pid and rxo.seq = s.seq
where s.hospcode = ? and s.pid = ?  and s.seq = ?');
$hps = explode(',',$_GET['servicex']);
mysqli_stmt_bind_param($stmt,"iii",$hps[0],$hps[1],$hps[2]);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt,$dname,$amount,$unit);
//$query = mysqli_query($conn,$sql) or die(mysql_error()) ;
echo "<table class='ui table'>" ;
echo "<tr><td>ชื่อยา</td><td>จำนวน</td><td>หน่วย</td></tr>" ;
while(mysqli_stmt_fetch($stmt)){
	echo "<tr>" ;
	echo '<td>'.$dname.'</td><td>'.$amount.'</td><td>'.$unit."</td></tr>" ;
}
echo "</table>" ;
?>
<iframe onload="showProced(<?php echo $_GET['servicex'] ; ?>)" frameborder='0' width='0' height='0'></iframe>
<?php
break;

case "proced":
$stmt = mysqli_prepare($conn,'select vn,icd9cm,icd9name from oprt where oprt.vn = ?') ;
mysqli_stmt_bind_param($stmt,"i",$_GET['vn']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$vn,$icd9cm,$icd9name);
//$sql = "select * from oprt where oprt.vn = ".$_GET['vn'] ;
//$query = mysqli_query($conn,$sql) or die(mysql_error()) ;
while(mysqli_stmt_fetch($stmt)){
	echo $icd9cm.' - '.$icd9name."<br>" ;
}
?>
<iframe onload="showCC(<?php echo $_GET['vn'] ; ?>)" frameborder='0' width='0' height='0'></iframe>
<?php
break;

case "cc":
$stmt = mysqli_prepare($conn,'select group_concat(symptom) as cc
from symptm where vn = ? group by vn') ;
mysqli_stmt_bind_param($stmt,"i",$_GET['vn']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$cc);
//$sql = "select group_concat(symptom) as cc
//from symptm where vn =".$_GET['vn']." group by vn" ;
//$query = mysqli_query($conn,$sql) or die(mysql_error()) ;
while(mysqli_stmt_fetch($stmt)){
	echo $cc."<br>" ;
}
?>

<iframe onload="showPI(<?php echo $_GET['vn'] ; ?>)" frameborder='0' width='0' height='0'></iframe>
<?php
break;

case "pi":
$stmt = mysqli_prepare($conn,'select group_concat(pillness) as pi
from pillness where vn  = ? group by vn') ;
mysqli_stmt_bind_param($stmt,"i",$_GET['vn']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$pi);

//$sql = "select group_concat(pillness) as pi
//from pillness where vn =".$_GET['vn']." group by vn" ;
//$query = mysqli_query($conn,$sql) or die(mysql_error()) ;
while(mysqli_stmt_fetch($stmt)){
	echo $pi."<br>" ;
}
?>
<iframe onload="showPE(<?php echo $_GET['vn'] ; ?>)" frameborder='0' width='0' height='0'></iframe>
<?php
break;
case "pe":
$stmt = mysqli_prepare($conn,'select sign
from sign where vn = ?') ;
mysqli_stmt_bind_param($stmt,"i",$_GET['vn']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$sign);

//$sql = "select sign
//from sign where vn =".$_GET['vn'] ;
//$query = mysqli_query($conn,$sql) or die(mysql_error()) ;
while(mysqli_stmt_fetch($stmt)){
	echo $sign."<br>" ;
}
?>

<iframe onload="showLAB(<?php echo $_GET['vn'] ; ?>)" frameborder='0' width='0' height='0'></iframe>
<?php
break;
case "lab":
$stmt = mysqli_prepare($conn,'select lbbk.labcode,lbbk.ln,lab.labname,lab.dbf
from lbbk inner join lab on lbbk.labcode = lab.labcode where lbbk.vn = ?') ;
mysqli_stmt_bind_param($stmt,"i",$_GET['vn']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$labcode,$ln,$labname,$dbf);

//$sql = "select lbbk.labcode,lbbk.ln,lab.labname,lab.dbf
//from lbbk inner join lab on lbbk.labcode = lab.labcode where lbbk.vn = ".$_GET['vn'] ;
//$query = mysqli_query($conn,$sql) ;
$i=1;
while(mysqli_stmt_fetch($stmt)){
	//echo "<a href='#myModal' data-toggle='modal' data-lab-ln=".$arr['ln']." data-lab-code=".$arr['labcode']." data-target='#view-modal'>".$arr['labname']."</a><br>" ;
	echo "<a href='#' class='SendButton' data-id='".$ln.",".$labcode.",".$labname.",".$dbf."' data-toggle='modal' data-target='#MyModal'>".$labname."</a><br>";
  $i = $i+1;
}
?>
<?php
}
?>
