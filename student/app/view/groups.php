<?php
if (!defined('NotDirectAccess')){
	die('Direct Access is not allowed to this page');
}
require_once 'header.php';
require_once 'navbar.php';

$grp = new group();
$myGroups = $grp->getMyGroups();
?>
<body class="bg-light" style="min-width:650px">

<div class="container mt-3">
  <div class="card">
    <div class="card-header">
      <strong class="card-title">Groups</strong>
    </div>
    <div class="card-body">
        <table class="table">
          <thead>
            <tr>
              <th scope="col">Test</th>
              <th scope="col">Instructor</th>
              <th scope="col">End date</th>
              <th scope="col">-</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($myGroups as $group){ ?>
              <tr>
                <th><?php echo $group->name ?></th>
                <td><?php echo $group->instructor ?></td>
                <td><?php echo date('m-d-Y h:i:s A', strtotime($group->joinDate));?></td>
                <td>
                  <button type="button" data-id="<?php echo $group->id ?>" class="btn btn-outline-danger mb-1 leaveGroupbtn">
                    <i class="fa fa-minus"></i> Leave Group
                  </button>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
    </div>
  </div>
</div>

<?php
require_once 'footer.php';
?>
