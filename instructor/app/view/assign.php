<?php
if (!defined('NotDirectAccess')){
	die('Direct Access is not allowed to this page');
}
require_once 'header.php';
require_once 'navbar.php';
if (isset($_GET['test']) && is_numeric($_GET['test'])) {
	$_tst = new test;
	$isValid = $_tst->checkID($_GET['test']);
	if(!$isValid){
		header("Location: ?tests");exit;
	}
?>
<div class="container">
	<div class="card">
		<div class="card-header">
			<h5 class="card-title">Assign Test</h5>
		</div>
		<div class="card-body w-100" style="padding-top: 0;padding-bottom: 0;">
		<div class="row p-3">
	<div class="col-12"> 
		<div id="viaGroupHeader" class="text-center">
			<h3><i class="fa fa-users" aria-hidden="true"></i> Via Group</h3>
			<strong>Student Registration Required.</strong>
			<p>Group members will log in via the website where they can access the tests you have assigned to their Group.</p>
		</div>
		<button type="button" class="btn btn-primary" id="viaGroupButton" data-toggle="collapse" data-target="#selectGroups">Assign To Group</button>
		<div class="collapse container" id="selectGroups">
			<form action="?assign&AssignToGroup" method="POST">
				<input type="hidden" name="testID" value="<?php echo $_GET['test'] ?>">
				<h5>1) Select Groups</h5>
				<?php
				$_group = new group();
				$groups = $_group->getAll();
				if (!empty($groups)) {
					foreach ($groups as $group) { ?>
						<div class="checkbox icheck-primary">
							<input type="radio" id="group<?php echo $group->id ?>" name="group" value="<?php echo $group->id ?>" checked/>
							<label for="group<?php echo $group->id ?>"><?php echo $group->name ?></label>
						</div>
					<?php }
				} else {
					echo '<p>You must <a href="?groups">Add New Group</a> before assigning test</p>';
					$noGroups = true;
				} ?>
				<h5>2) Pre-set settings</h5>
				<?php
				$_assign = new assign();
				$settings = $_assign->getSettings();
				foreach ($settings as $setting) { ?>
					<div class="checkbox icheck-primary">
						<input type="radio" id="setting<?php echo $setting->id ?>" name="settingID" value="<?php echo $setting->id ?>" />
						<label for="setting<?php echo $setting->id ?>"><?php echo $setting->name ?>'s Setting</label>
					</div>
				<?php } ?>
				<div class="checkbox icheck-primary">
					<input type="radio" id="newSettings" name="settingID" value="0" checked />
					<label for="newSettings">Create New Settings</label>
				</div>
				<input class="btn btn-success" type="submit" id="AssignToGroupbtn" value="Next" <?php echo (isset($noGroups) ? 'disabled' : ''); ?>>
			</form>
		</div>
	</div>
</div>
		</div>
	</div>
</div>

<?php 
}elseif (isset($_GET['AssignToGroup']) && isset($_POST['group']) && isset($_POST['testID'])) {
    $_group = new group();
    $group = $_group->getByID($_POST['group']);
    $_assign = new assign();
    $_test = new test();
    if (isset($_POST['settingID']) and $_POST['settingID'] > 0)
        $setting = $_assign->getSettingByID($_POST['settingID']);
    else
        $setting = (object)[
            'startTime' => date('Y-m-d\TH:i'),
            'endTime' => date('Y-m-d\TH:i'),
            'duration' => 30,
            'prevQuestion' => 0,
            'viewAnswers' => 2,
            'releaseResult' => 1,
            'sendToStudent' => 1,
            'sendToInstructor' => 1,
            'passPercent' => 60
        ];
    if ($_test->checkID($_POST['testID'])) { ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Assigning Test To [<?php echo $group->name ?>]</h5>
            </div>
            <div class="card-body">
                <form action="app/controller/assign.inc.php?assignToGroup" id="groupAssignForm" method="post">
                    <input type="hidden" name="testID" value="<?php echo $_POST['testID'] ?>">
                    <input type="hidden" name="groupID" value="<?php echo $_POST['group'] ?>">
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label for="groupName" class=" form-control-label">Group </label>
                        </div>
                        <div class="col-8">
                            <input type="text" name="groupName" class="form-control" value="<?php echo $group->name ?>" disabled>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label class="form-control-label">Start Time </label>
                        </div>
                        <div class="col-8">
                            <div class="input-group date" id="startTimePicker" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" data-datetime="<?php echo date('Y-m-d H:i a',strtotime($setting->startTime)) ?>" name="startTime" data-target="#startTimePicker"/>
                                <div class="input-group-append" data-target="#startTimePicker" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label class="form-control-label">End Time </label>
                        </div>
                        <div class="col-8">
                            <div class="input-group date" id="endTimePicker" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" data-datetime="<?php echo date('Y-m-d H:i a',strtotime($setting->endTime)) ?>" name="endTime"  data-target="#endTimePicker"/>
                                <div class="input-group-append" data-target="#endTimePicker" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label for="prevQuestion" class=" form-control-label"> </label>
                        </div>
                        <div class="col-8">
                            <div class="checkbox icheck-pumpkin">
                                <input type="checkbox" id="prevQuestion" name="prevQuestion" <?php echo (($setting->prevQuestion)? 'checked':'') ?> />
                                <label for="prevQuestion">Student Can Return To Previous Question</label>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label for="duration" class=" form-control-label">Test Duration </label>
                        </div>
                        <div class="col-6">
                            <input type="range" class="custom-range" min="5" max="90" step="5" value="<?php echo $setting->duration ?>" name="duration" id="duration" oninput="$('#durationText').text(this.value)" onchange="$('#durationText').text(this.value)" required>
                        </div>
                        <div class="col-2">
                            <label class="badge badge-primary"><span id="durationText"><?php echo $setting->duration ?></span> Minutes</label>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label for="percent" class=" form-control-label">Test Pass Percent </label>
                        </div>
                        <div class="col-8">
                            <input type="number" name="percent" id="percent" min="1" max="100" placeholder="Pass Percent..." class="form-control" value="<?php echo $setting->passPercent ?>" required>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label class=" form-control-label">Release Results </label>
                        </div>
                        <div class="col-8">
                            <div class="radio icheck-info">
                                <input type="radio" id="sh11" name="releaseResult" value="0" <?php echo (($setting->releaseResult == 0)? 'checked':'') ?>/>
                                <label for="sh11">Release Manually</label>
                            </div>
                            <div class="radio icheck-info">
                                <input type="radio" id="sh12" name="releaseResult" value="1" <?php echo (($setting->releaseResult == 1)? 'checked':'') ?> />
                                <label for="sh12">Release Automatically After Test</label>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label class=" form-control-label">Show Test Answers </label>
                        </div>
                        <div class="col-8">
                            <div class="radio icheck-info">
                                <input type="radio" id="sh1" name="showAnswers" value="0" <?php echo (($setting->viewAnswers == 0)? 'checked':'') ?>/>
                                <label for="sh1">Show Directly After Submit</label>
                            </div>
                            <div class="radio icheck-info">
                                <input type="radio" id="sh2" name="showAnswers" value="1" <?php echo (($setting->viewAnswers == 1)? 'checked':'') ?> />
                                <label for="sh2">Show After Test Session</label>
                            </div>
                            <div class="radio icheck-info">
                                <input type="radio" id="sh3" name="showAnswers" value="2" <?php echo (($setting->viewAnswers == 2)? 'checked':'') ?> />
                                <label for="sh3">Don't Show Answers</label>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col col-md-3">
                            <label for="duration" class=" form-control-label">Send Results To Mail </label>
                        </div>
                        <div class="col-8">
                            <div class="checkbox icheck-pumpkin">
                                <input type="checkbox" id="se1" name="sendToS" <?php echo (($setting->sendToStudent)? 'checked':'') ?> />
                                <label for="se1">Send To Student Mail</label>
                            </div>
                            <div class="checkbox icheck-pumpkin">
                                <input type="checkbox" id="se2" name="sendToI"  <?php echo (($setting->sendToInstructor)? 'checked':'') ?> />
                                <label for="se2">Send To Instructor Mail</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <input class="btn btn-primary submitAssign" type="submit" form="groupAssignForm" value="Save" style="float: right;">
            </div>
        </div>
    <?php
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER']);exit;
    }
}
define('ContainsDatatables', true);
define('TimePicker', true);

require_once 'footer.php';
?>