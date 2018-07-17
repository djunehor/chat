<?php
$page_name = "All Chats";
include '../views/manager_header.php'; ?>
 <link href="../lib/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../lib/Ionicons/css/ionicons.css" rel="stylesheet">
    <link href="../lib/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
    <link href="../lib/highlightjs/github.css" rel="stylesheet">
    <link href="../lib/datatables/jquery.dataTables.css" rel="stylesheet">
    <link href="../lib/select2/css/select2.min.css" rel="stylesheet">

    <!-- Starlight CSS -->
    <link rel="stylesheet" href="../css/starlight.css">
    <!-- ########## START: MAIN PANEL ########## -->
  <div class="sl-mainpanel">
     <nav class="breadcrumb sl-breadcrumb">
        <a class="breadcrumb-item" href="index"><?php echo $option['website_name']; ?></a>
        <span class="breadcrumb-item active"><?php echo $page_name; ?></span>
      </nav>

      <div class="sl-pagebody">
	  <div class="card pd-20 pd-sm-40">
          <h6 class="card-body-title">All Chats</h6>
         <p class="mg-b-20 mg-sm-b-30">List of your chat history.</p>

          <div class="table-wrapper">
		   <div class=" alert alert-danger pname_error_show" style="display:none"></div>
<table class="table table-bordered table-striped">
<tr>
<th>Email</th>
<th>Last Message</th>
<th>Date</th>
<th>Action</th>
<th></th>
</tr>
<?php
$last_id = 0;
$tabla = mysqli_query($con,"
SELECT c.ID as chat_id, u.email, m.body, m.created_at FROM $user_table u
INNER JOIN $chat_table c
ON (u.ID = c.user1 OR u.ID = c.user2) AND c.abolished=0
INNER JOIN $message_table m
ON m.chat_id = c.ID
WHERE u.ID = c.user1 OR u.ID = c.user2 AND u.ID != $ID
") or die(mysqli_error($con));
while ($chat = mysqli_fetch_array($tabla)) {
    if($chat['chat_id'] != $last_id) {
        $last_id = $chat['chat_id'];
        echo "
<tr id=\"chat".$chat['chat_id']."\">
<td>" . $chat["email"] . "</td>";
        $chatid = $chat['chat_id'];
        echo "<td>" . html_entity_decode(htmlspecialchars_decode($chat['body'])) . "</td>";
        echo "<td>" . date('d M Y g:i a', strtotime($chat['created_at'])) . "</td>
<td><a href=\"ViewChat?id=" . $chat["chat_id"] . "\">View Chat</a></td>
<td><button onclick=\"del_chat(this.value)\" id=\"btnDelete\" type=\"submit\" value=\"" . $chat['chat_id'] . "\" class=\"btn btn-danger\">Delete</button></td>               
</tr>";
    }
}
?>
</table>

          </div><!-- table-wrapper -->
        </div><!-- card -->
		<script>
			function del_chat(value){
			    var chat_id = value;
	$.post("<?php echo $website_url; ?>/ajax_form_process?value=del_chat",{pid:chat_id},function(data){
		if(data.length != 0){
			$('.pname_error_show').show();
			$('.pname_error_show').html(data);
		}else{
			$('.pname_error_show').hide();
			$('#btnDelete').removeAttr('disabled');
            $('#chat'+chat_id).hide();
		}
	});
} </script>
          <script>
$('#datatable1').DataTable({
responsive: true,
language: {
  searchPlaceholder: 'Search...',
  sSearch: '',
  lengthMenu: '_MENU_ items/page',
}
});</script>
    <script src="../lib/popper.js/popper.js"></script>
    <script src="../lib/bootstrap/bootstrap.js"></script>
    <script src="../lib/perfect-scrollbar/js/perfect-scrollbar.jquery.js"></script>
    <script src="../lib/highlightjs/highlight.pack.js"></script>
    <script src="../lib/datatables/jquery.dataTables.js"></script>
    <script src="../lib/datatables-responsive/dataTables.responsive.js"></script>
    <script src="../lib/select2/js/select2.min.js"></script>

    <script src="../js/starlight.js"></script>
    <script>
      $(function(){
        'use strict';

        $('#datatable1').DataTable({
          responsive: true,
          language: {
            searchPlaceholder: 'Search...',
            sSearch: '',
            lengthMenu: '_MENU_ items/page',
          }
        });

        $('#datatable2').DataTable({
          bLengthChange: false,
          searching: false,
          responsive: true
        });

        // Select2
        $('.dataTables_length select').select2({ minimumResultsForSearch: Infinity });

      });
    </script> 
</div>

    <!-- ########## END: MAIN PANEL ########## -->
<?php include '../views/manager_footer.php'; ?>