<?php
// +------------------------------------------------------------------------+
// | PHP Melody ( www.phpsugar.com )
// +------------------------------------------------------------------------+
// | PHP Melody IS NOT FREE SOFTWARE
// | If you have downloaded this software from a website other
// | than www.phpsugar.com or if you have received
// | this software from someone who is not a representative of
// | PHPSUGAR, you are involved in an illegal activity.
// | ---
// | In such case, please contact: support@phpsugar.com.
// +------------------------------------------------------------------------+
// | Developed by: PHPSUGAR (www.phpsugar.com) / support@phpsugar.com
// | Copyright: (c) 2004-2013 PHPSUGAR. All rights reserved.
// +------------------------------------------------------------------------+

$showm = '2';
/*
$load_uniform = 0;
$load_ibutton = 0;
$load_tinymce = 0;
$load_swfupload = 0;
$load_colorpicker = 0;
$load_prettypop = 0;
*/
$load_scrolltofixed = 1;
$load_chzn_drop = 1;
$load_tagsinput = 1;
$load_uniform = 1;
$load_tinymce = 1;
$load_swfupload = 1;
$load_swfupload_upload_image_handlers = 1;
$load_fileinput_upload = 1;

$_page_title = 'Add video stream';
include('header.php');

define('PHPMELODY', true);

$step = 2;

$inputs = array('source_id' => 0,
				'language' => 1,
				'age_verification' => 0,
				'featured' => 0,
				'restricted' => 0,
				'allow_comments' => 1,
				'allow_embedding' => 1 
				);


if ($_POST['submit'] != '')
{
	$return_msg = '';

	foreach ($_POST as $k => $v)
	{
		if ( ! is_array($_POST[$k]))
			$_POST[$k] = stripslashes(trim($v));
	}

	if (strlen($_POST['video_title']) == 0)
	{
		$return_msg = 'Insert the video title';
	}
	else if ((is_array($_POST['category']) && pm_count($_POST['category']) == 0) || ( ! isset($_POST['category'])))
	{
		$return_msg = 'Please select a category for this video';
	}
	if ($return_msg == '')
	{
		$video_details = array(	'uniq_id' => '',
								'video_title' => '',
								'description' => '',
								'yt_id' => '',
								'category' => '',
								'submitted_user_id' => 0,
								'submitted' => '',
								'source_id' => 0,
								'language' => 1,
								'age_verification' => 0,
								'url_flv' => '',
								'yt_thumb' => '',
								'yt_thumb_local' => '',
								'mp4' => '',
								'direct' => '',
								'tags' => '',
								'featured' => 0,
								'added' => '',
								'restricted' => 0,
								'allow_comments' => 1,
								'allow_embedding' => 1,
								'jw_flashvars' => array('provider' => '',
														'startparam' => '',
														'loadbalance' => '',
														'subscribe' => '',
														'securetoken' => ''
												  )
							);
		
		$video_details['submitted_user_id'] = (int) $userdata['id'];
		$video_details['submitted']   = $userdata['username'];
		$video_details['featured'] 	  = (int) $_POST['featured'];
		$video_details['description'] = $_POST['description'];
		$video_details['yt_thumb'] 	  = $_POST['yt_thumb'];
		$video_details['yt_thumb_local'] = $_POST['yt_thumb_local'];
		$video_details['video_title'] = $_POST['video_title'];
		$video_details['category'] 	  = (is_array($_POST['category'])) ? implode(',', $_POST['category']) : $_POST['category'];
		$video_details['tags'] 		  = $_POST['tags'];
		$video_details['direct']	  = $_POST['direct'];
		$video_details['restricted']  = (int) $_POST['restricted'];
		$video_details['jw_flashvars']['provider'] 			= $_POST['jw_provider'];
		$video_details['meta']		  = $_POST['meta'];
		$video_details['allow_comments'] = (int) $_POST['allow_comments'];
		$video_details['allow_embedding'] = (int) $_POST['allow_embedding'];

		if ($_POST['jw_provider'] == 'rtmp')
		{
			$video_details['jw_flashvars']['loadbalance'] 	= $_POST['jw_rtmp_loadbalance'];
			$video_details['jw_flashvars']['subscribe'] 	= $_POST['jw_rtmp_subscribe'];
			$video_details['jw_flashvars']['securetoken']	= $_POST['jw_securetoken'];
		}
		else if ($_POST['jw_provider'] == 'http')
		{
			$video_details['jw_flashvars']['startparam'] 	= trim($_POST['jw_startparam']);
		}

		// file + streamer combination makes our url_flv unique
		$video_details['url_flv'] = trim($_POST['jw_file']) .';'. trim($_POST['jw_streamer']);

		$added = validate_item_date($_POST);
		if ($added === false)
		{
			$return_msg .= 'Invalid date given <br />';
		}
		else
		{
			$video_details['added'] = pm_mktime($added);
		}

		// check if stream has been added previously
		if ($video_details['url_flv'] != '')
		{
			$sql = "SELECT uniq_id, COUNT(*) as total_found FROM pm_videos WHERE url_flv = '". secure_sql($video_details['url_flv']) ."'";
			$result = mysql_query($sql);
			$row = mysql_fetch_assoc($result);
			mysql_free_result($result);

			if ($row['total_found'] > 0)
			{
				$return_msg .= 'The video you\'re trying to add was found in your database. ';
				$return_msg .= '<a href="'. _URL .'/watch.php?vid='. $row['uniq_id'] .'" />View</a> or <a href="edit-video.php?vid='. $row['uniq_id'] .'">Edit</a> this video.';
			}
		}
		
		$uniq_id = generate_video_uniq_id();

		$video_details['uniq_id'] = $uniq_id;
		$video_details['yt_id'] = $uniq_id;
		if ($video_details['yt_thumb_local'] != '')
		{
			$tmp_parts = explode('/', $video_details['yt_thumb_local']);
			$thumb_filename = array_pop($tmp_parts);
			$tmp_parts = explode('.', $thumb_filename);
			$thumb_ext = array_pop($tmp_parts);
			$thumb_ext = strtolower($thumb_ext);
			$renamed = false;

			if (file_exists(_THUMBS_DIR_PATH . $thumb_filename))
			{
				if (rename(_THUMBS_DIR_PATH . $thumb_filename, _THUMBS_DIR_PATH . $uniq_id . '-1.'. $thumb_ext))
				{
					$video_details['yt_thumb'] = $uniq_id . '-1.'. $thumb_ext;
					$renamed = true;
				}
			}

			if ( ! $renamed)
			{
				$video_details['yt_thumb'] = $video_details['yt_thumb_local'];
			}
		}
		else
		{
			$video_details['yt_thumb'] = $video_details['yt_thumb'];
		}

		if (strlen($return_msg) == 0)
		{
		do{
			$dobreak = false;
			if($dobreak) break;
			$new_video = insert_new_video($video_details, $new_video_id);
			if($dobreak) break;
			if($new_video !== true)
			{
				$return_msg = "<em>A problem occurred! Couldn't add the new stream to your database;</em><br /><strong>MySQL Reports:</strong> ".$new_video[0]."<br /><strong>Error Number:</strong> ".$new_video[1]."<br />";
			}
			else
			{
				//	tags?
				if($video_details['tags'] != '')
				{
					$tags = explode(",", $_POST['tags']);
					foreach($tags as $k => $tag)
					{
						$tags[$k] = stripslashes(trim($tag));
					}
					//	remove duplicates and 'empty' tags
					$temp = array();
					for($i = 0; $i < pm_count($tags); $i++)
					{
						if($tags[$i] != '')
							if($i <= (pm_count($tags)-1))
							{
								$found = 0;
								for($j = $i + 1; $j < pm_count($tags); $j++)
								{
									if(strcmp($tags[$i], $tags[$j]) == 0)
										$found++;
								}
								if($found == 0)
									$temp[] = $tags[$i];
							}
					}
					$tags = $temp;
					//	insert tags
					if(pm_count($tags) > 0)
						insert_tags($uniq_id, $tags);
				}
				$step = 3;
				$return_msg = 'The stream has been added.';
			}
		}while(false);
		}
	}	//	endif $return_msg == ''

	$inputs = $_POST;
}
?>
<script type="text/javascript">
	$(document).ready(function(){
		switch ($('select[name="jw_provider"]').val())
		{
			default:
			case '':
				$('.provider_http').hide();
				$('.provider_rtmp').hide();
			break;
			case 'rtmp':
				$('.provider_http').hide();
			break;
			case 'http':
				$('.provider_rtmp').hide();
			break;

		}

		$('select[name="jw_provider"]').change(function(){
			switch(($(this).val()))
			{
				default:
				case '':
					$('.provider_http').fadeOut('fast');
					$('.provider_rtmp').fadeOut('fast');
				break;
				case 'rtmp':
					$('.provider_http').hide();
					$('.provider_rtmp').fadeIn('slow');
				break;
				case 'http':
					$('.provider_rtmp').hide();
					$('.provider_http').fadeIn('slow');
				break;
			}
		});
	});
</script>
<!-- Main content -->
<div class="content-wrapper">

<div class="page-header-wrapper page-header-edit">
	<div class="page-header page-header-light">
		<div class="page-header-content header-elements-md-inline">
		<div class="d-flex justify-content-between w-100">
			<div class="page-title d-flex">
				<h4><span class="font-weight-semibold"><?php echo $_page_title; ?></h4>
			</div>
			<div class="header-elements d-flex-inline align-self-center ml-auto">
				<div class="">
					<a href="videos.php" class="btn btn-sm btn-outline alpha-grey text-grey-400 border-transparent border-2 pm-show-loader">Cancel</a>
					<button type="submit" name="submit" value="Submit" class="btn btn-sm btn-outline alpha-success text-success-400 border-success-400 border-2" onclick="document.forms[0].submit({return validateFormOnSubmit(this, 'Please fill in the required fields (highlighted)')});" form="embed_video"><i class="mi-check"></i> Save</button>
				</div>
			</div>
		</div>
		</div>

		<div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
			<div class="d-flex">
				<div class="breadcrumb">
					<a href="index.php" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
					<a href="videos.php" class="breadcrumb-item">Videos</a>
					<a href="add-video-stream.php" class="breadcrumb-item active"><?php echo $_page_title; ?></a>
				</div>
			</div>

			<div class="header-elements d-none d-md-block"><!--d-none-->
				<div class="breadcrumb justify-content-center">
					<a href="#" id="show-help-assist" class="breadcrumb-elements-item"><i class="mi-help-outline text-muted"></i></a>
				</div>
			</div>
		</div>
	</div><!--.page-header -->
</div><!--.page-header-wrapper-->	
<div class="page-help-panel" id="help-assist"> 
		<div class="row">
			<div class="col-2 help-panel-nav">
				<div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
					<a class="nav-link active" id="v-pills-tab-help-one" data-toggle="pill" href="#v-pills-one" role="tab" aria-controls="v-pills-one" aria-selected="true" data-toggle="tab">Overview</a>
				</div>
			</div>
			<div class="col-10 help-panel-content">
				<div class="tab-content" id="v-pills-tabContent">
					<div class="tab-pane show active" id="v-pills-one" role="tabpanel" aria-labelledby="v-pills-tab-help-one">
						<p>You can use PHP MELODY to add live video streams. Each new submission has a few required fields such as Title, Stream URL and Category. Other options listed on this page are optional.</p>
						<p>Assigning a thumbnail can be done either by URL input or direct upload. If you'd rather use a URL instead of uploading your thumbnail, click on the thumbnail image to reveal the hidden field.</p>
						<p>Each submission can be published at a specified date in the future if needed. Also, all your videos can placed behind a registration wall thus, increasing your registration rate.</p>
						<p></p>
						<p>Learn how to use the <strong>custom fields</strong>: <a href="http://help.phpmelody.com/how-to-use-the-custom-fields/" target="_blank">http://help.phpmelody.com/how-to-use-the-custom-fields/</a></p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- /page header -->


	<!-- Content area -->
	<div class="content content-edit-page">

<?php

	if ($step == 2)
	{
		if (strlen($return_msg) > 0)
		{
			echo pm_alert_error($return_msg);
		}
?>

<form name="embed_video" id="embed_video" method="post" enctype="multipart/form-data" action="add-video-stream.php" onsubmit="return validateFormOnSubmit(this, 'Please fill in the required fields (highlighted)')">
<div class="row">
	<div class="col-sm-12 col-md-9">
		<div class="card">
			<div class="card-header bg-white header-elements-inline">
				<h6 class="card-title font-weight-semibold">Title and Description</h6>
				<div class="header-elements">
					<div class="list-icons">
					</div>
				</div>
			</div>
			<div class="card-body">
				<?php echo $video_lookup; ?>
				<input name="video_title" type="text" class="form-control form-required font-weight-semibold font-size-lg" value="<?php echo $inputs['video_title']; ?>" />
				<div class="permalink-field mt-2 mb-2">
					<?php if (_SEOMOD) : ?>
					<strong>Permalink:</strong> <?php echo _URL .'/';?><input class="permalink-input" type="text" name="video_slug" value="<?php echo urldecode($video_details['video_slug']);?>" value="<?php echo urldecode($video_details['video_slug']);?>" /><?php echo '_UniqueID.html';?>
					<?php endif; ?>	
				</div>

				<div id="textarea-dropzone" class="upload-file-dropzone">

					<div class="fileinput-button">
						<input type="file" name="file" id="upload-file-wysiwyg-btn" class="file-input file-input-custom form-control form-control-sm alpha-grey" multiple="multiple" data-browse-icon="<i class='icon-upload4 mr-2'></i>" data-browse-label="Upload images" data-show-caption="false" data-show-upload="false" data-browse-class="btn btn-link btn-sm text-default font-weight-semibold" data-remove-class="btn btn-light btn-sm" data-show-remove="false" data-show-preview="false" data-fouc />
					</div>

					<textarea name="description" cols="100" id="textarea-WYSIWYG" class="tinymce"><?php echo nl2br($inputs['description']); ?></textarea>
					<span class="autosave-message"></span>
				</div>					
			</div>
		</div><!--.card-->

		<div class="card">
			<div class="card-header bg-white header-elements-inline">
				<h6 class="card-title font-weight-semibold">Video Details</h6>
			</div>
				<?php
				if($video_details['yt_length'] > 0) {	
					$yt_minutes = intval($video_details['yt_length'] / 60);
					$yt_seconds = intval($video_details['yt_length'] % 60); 
				} else {
					$yt_minutes = 0;
					$yt_seconds = 0;
				}
				?>
				<ul class="nav nav-tabs nav-tabs-bottom">
					
					<li class="nav-item"><a href="#badge-tab0" class="nav-link active" data-toggle="tab">Tags</a></li>
					<li class="nav-item"><a href="#badge-tab1" class="nav-link" data-toggle="tab">Duration <span class="badge badge-flat border-primary text-primary-600 border-0 alpha-primary" id="value-yt_length"><strong><?php echo $yt_minutes; ?> min. <?php echo $yt_seconds; ?> sec.</strong></span></a></li>
					<li class="nav-item"><a href="#badge-tab2" class="nav-link" data-toggle="tab">Comments <span class="badge badge-flat border-primary text-primary-600 border-0 <?php echo ($inputs['allow_comments'] == 1) ? 'alpha-success text-success-600' : 'alpha-primary';?>" id="value-comments"><strong><?php echo ($inputs['allow_comments'] == 1) ? 'allowed' : 'closed';?></strong></span></a></li>
					<li class="nav-item"><a href="#badge-tab3" class="nav-link" data-toggle="tab">Embedding <span class="badge badge-flat border-primary text-primary-600 border-0 <?php echo ($inputs['allow_embedding'] == 1) ? 'alpha-success text-success-600' : 'alpha-primary';?>" id="value-embedding"><strong><?php echo ($inputs['allow_embedding'] == 1) ? 'allowed' : 'disallowed';?></strong></span></a></li>
					<li class="nav-item"><a href="#badge-tab4" class="nav-link" data-toggle="tab">Featured <span class="badge badge-flat border-primary text-primary-600 border-0 <?php echo ($inputs['featured'] == 1) ? 'alpha-success text-success-600' : 'alpha-primary';?>" id="value-featured"><strong><?php echo ($inputs['featured'] == 1) ? 'yes' : 'no';?></strong></span></a></li>
					<li class="nav-item"><a href="#badge-tab5" class="nav-link" data-toggle="tab">Private <span class="badge badge-flat border-primary text-primary-600 border-0 alpha-primary" id="value-register"><strong><?php echo ($inputs['restricted'] == 1) ? 'yes' : 'no';?></strong></span></a></li>
					<li class="nav-item"><a href="#badge-tab7" class="nav-link" data-toggle="tab">Publish <span class="badge badge-flat border-primary text-primary-600 border-0 alpha-primary" id="value-publish"><strong>now</strong></span></a></li>

					<li class="nav-item dropdown">
						<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Other</a>
						<div class="dropdown-menu dropdown-menu-right">
							<a href="#badge-tab6" class="dropdown-item" data-toggle="tab">Views </span></a>
							<a href="#badge-tab8" class="dropdown-item" data-toggle="tab">Submitted by</strong></span></a>
						</div>
					</li>

				</ul>

			<div class="card-body pt-0">
				<div class="tab-content">
					<div class="tab-pane show active" id="badge-tab0">
						<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Video Tags:</div>
						<div class="tagsinput bootstrap-tagsinput">
							<input type="text" id="tags_addvideo_1" name="tags" value="<?php echo $inputs['tags']; ?>" class="tags form-control tags-input" />
						</div>
					</div>
					<div class="tab-pane" id="badge-tab1">
						<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Video Duration:</div>
						<div class="input-group input-group-sm custom-date-form">
							<input type="text" name="yt_min" id="yt_length" value="<?php echo $yt_minutes; ?>" size="4" class="form-control form-control-sm" />
								<span class="input-group-append">
								<span class="input-group-text">min.</span>
							</span>
							<input type="text" name="yt_sec" id="yt_length" value="<?php echo $yt_seconds; ?>" size="3" class="form-control form-control-sm" /> 
							<span class="input-group-append">
								<span class="input-group-text">sec.</span>
							</span>
						</div>

						<input type="hidden" name="yt_length" id="yt_length" value="<?php echo trim(($yt_minutes * 60) + $yt_seconds); ?>" />
					</div>

					<div class="tab-pane" id="badge-tab2">
							<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Comments:</div>
							<label><input name="allow_comments" id="allow_comments" type="checkbox" value="1" <?php if ($inputs['allow_comments'] == 1) echo 'checked="checked"';?> /> Allow comments on this video</label>
							<?php if ($config['comment_system'] == 'off') : ?>
							<div class="alert alert-info">
							Comments are disabled site-wide. 
							<br />
							To enable comments, visit the <a href="settings.php?view=comment" title="Settings page" target="_blank">Settings</a> page.
							</div>
							<?php endif;?>
					</div>

					<div class="tab-pane" id="badge-tab3">
							<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Video Embedding:</div>
							<label><input name="allow_embedding" id="allow_embedding" type="checkbox" value="1" <?php if ($inputs['allow_embedding'] == 1) echo 'checked="checked"';?> /> Allow embedding to 3rd party sites</label>
							<?php if ($config['allow_embedding'] == '0') : ?>
							<div class="alert alert-info">
							Embedding is disabled site-wide. 
							<br />
							To enable embedding, visit the <a href="settings.php?view=video" title="Settings page" target="_blank">Settings</a> page.
							</div>
							<?php endif;?>
					</div>

					<div class="tab-pane" id="badge-tab4">
							<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Mark this video as featured:</div>
							<label><input type="checkbox" name="featured" id="featured" value="1" <?php if($inputs['featured'] == 1) echo 'checked="checked"';?> /> Yes, mark as featured</label>
					</div>

					<div class="tab-pane" id="badge-tab5">
							<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Require registration to watch video:</div>
							<label class="checkbox inline"><input type="radio" name="restricted" id="restricted" value="1" <?php if ($inputs['restricted'] == 1) echo 'checked="checked"'; ?> /> Yes</label>
							<label class="checkbox inline"><input type="radio" name="restricted" id="restricted" value="0" <?php if ($inputs['restricted'] == 0) echo 'checked="checked"'; ?> /> No</label>
					</div>

					<div class="tab-pane" id="badge-tab6">
							<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Views:</div>
							<input type="hidden" name="site_views" value="<?php echo $inputs['site_views'];?>" />
							<input type="text" name="site_views_input" id="site_views_input" value="<?php echo $inputs['site_views']; ?>" size="10" class="form-control col-md-3" />
					</div>

					<div class="tab-pane" id="badge-tab7">
						<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Publish date:</div>
						<?php echo ($_POST['date_month'] != '') ? show_form_item_date( pm_mktime($_POST) ) : show_form_item_date();	?>
						<div class="text-muted mt-2">You can schedule videos to be available at a future date.</div>
					</div>

					<div class="tab-pane" id="badge-tab8">
							<div class="text-uppercase font-size-xs font-weight-semibold mb-2">Submitted by:</div>
							<input type="text" name="submitted" id="submitted" value="<?php echo htmlspecialchars($inputs['submitted']); ?>" class="form-control col-md-3" />
							<!-- <span class="text-danger text-sm">Use only a valid username!</span> -->
					</div>

				</div>
			</div>
		</div><!--.card-->

		<div class="card">
			<div class="card-header bg-white header-elements-inline header-toggles" data-target="#cardVideoSource" data-toggle="collapse" aria-expanded="false" aria-controls="cardVideoSource">
				<h6 class="card-title font-weight-semibold">Video Source</h6>
				<div class="header-elements">
					<div class="list-icons">
						<a href="#" data-target="#cardVideoSource" data-toggle="collapse" aria-expanded="false" aria-controls="cardVideoSource" class="text-default collapsed"><i class="icon-arrow-up12"></i></a>
					</div>
				</div>
			</div>
			<div class="collapse show" id="cardVideoSource">
				<div class="card-body">

					<div class="form-group row">
						<label class="col-lg-3 col-form-label">
							File location (URL):
							<i class="mi-info-outline" data-popup="popover" data-trigger="hover" title="" data-html="true" data-content="Internal URL of video or audio file you want to stream.<br />This is the equivalent of JW Player's <code><i>file</i></code> flashvar. "></i>
							</label>
						<div class="col-lg-9">
							<input name="jw_file" type="text" class="form-control form-required" placeholder="http://" value="<?php echo $inputs['jw_file']; ?>" />
						</div>
					</div>

					<div class="form-group row">
						<label class="col-lg-3 col-form-label">
							Streamer:
							<i class="mi-info-outline" rel="popover" data-trigger="hover" data-animation="true" title="" data-content="Location of an RTMP or HTTP server instance to use for streaming."></i> 
							</label>
						<div class="col-lg-9">
							<input name="jw_streamer" type="text" class="form-control form-required" value="<?php echo $inputs['jw_streamer']; ?>" />
						</div>
					</div>

					<div class="form-group row">
						<label class="col-lg-3 col-form-label">
							Provider (<small>Optional</small>):
							<i class="mi-info-outline" rel="popover" data-trigger="hover" data-animation="true" title="" data-content="RTMP or HTTP"></i>
							</label>
						<div class="col-lg-9">
							<select name="jw_provider" class="custom-select col-md-3">
								<option value=''></option>
								<option value="rtmp" <?php echo ($_POST['provider'] == 'rtmp') ? 'selected="selected"' : '';?>>RTMP</option>
								<option value="http" <?php echo ($_POST['provider'] == 'http') ? 'selected="selected"' : '';?>>HTTP</option>
							</select>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-lg-3 col-form-label">
							Load Balancing (<small>Optional</small>):
							<i class="mi-info-outline" rel="popover" data-html="true" data-trigger="hover" title="" data-content="This is the equivalent of JW Player's <code><i>rtmp.loadbalance</i></code> flashvar."></i>
							</label>
						<div class="col-lg-9">
							<label><input class="checkbox inline" type="radio" name="jw_rtmp_loadbalance" value="true" <?php echo ($inputs['jw_rtmp_loadbalance'] == 'true') ? 'checked="checked"' : '';?> /> On</label> 
							<label><input class="checkbox inline" type="radio" name="jw_rtmp_loadbalance" value="" <?php echo ($inputs['jw_rtmp_loadbalance'] != 'true') ? 'checked="checked"' : '';?> /> Off</label>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-lg-3 col-form-label">
							Subscribe (<small>Optional</small>):
							<i class="mi-info-outline" rel="popover" data-trigger="hover" data-animation="true" title="" data-content="This is the equivalent of JW Player's <code>rtmp.subscribe</code> flashvar."></i>
							</label>
						<div class="col-lg-9">
							<label><input class="checkbox inline" type="radio" name="jw_rtmp_subscribe" value="true" <?php echo ($inputs['jw_rtmp_subscribe'] == 'true') ? 'checked="checked"' : '';?> /> Yes</label> 
							<label><input class="checkbox inline" type="radio" name="jw_rtmp_subscribe" value="" <?php echo ($inputs['jw_rtmp_subscribe'] != 'true') ? 'checked="checked"' : '';?> /> No</label>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-lg-3 col-form-label">
							Secure Token (<small>Optional</small>):
							<i class="mi-info-outline" rel="popover" data-html="true" data-trigger="hover" data-animation="true" title="" data-content="Some service providers (e.g Wowza Media Server) have a feature called Secure Token that is used to protect your streams from downloading.<br />This <code>securetoken</code> parameter is optional and might not be compatible with all RTMP Service providers."></i>
							</label>
						<div class="col-lg-9">
							<input type="text" name="jw_securetoken" value="<?php echo $inputs['jw_securetoken'] ;?>" size="20" class="form-control" />
						</div>
					</div>

					<div class="form-group row">
						<label class="col-lg-3 col-form-label">
							Startparam (<small>Optional</small>):
							<i class="mi-info-outline" rel="popover" data-html="true" data-trigger="hover" data-animation="true" title="" data-content="This is the equivalent of JW Player's <code><i>rtmp.startparam</i></code> flashvar."></i>
							</label>
						<div class="col-lg-9">
							<input type="text" name="jw_startparam" value="<?php echo $inputs['jw_startparam'];?>" size="20" class="form-control" />
						</div>
					</div>

				</div>
			</div>
		</div><!--.card-->

		<div class="card">
			<div class="card-header bg-white header-elements-inline header-toggles" data-target="#cardCustomFields" data-toggle="collapse" aria-expanded="false" aria-controls="cardCustomFields">
				<h6 class="card-title font-weight-semibold">Custom Fields</h6>
				<div class="header-elements">
					<div class="list-icons">
						<a href="http://help.phpmelody.com/how-to-use-the-custom-fields/" rel="popover" data-trigger="hover" data-animation="true" data-content="Click here to learn more about the 'Custom Fields'" target="_blank" class="text-dark"><i class="mi-info-outline"></i></a>
						<a href="#" data-target="#cardCustomFields" data-toggle="collapse" aria-expanded="false" aria-controls="cardCustomFields" class="text-default collapsed"><i class="icon-arrow-up12"></i></a>
					</div>
				</div>
			</div>
			<div class="collapse" id="cardCustomFields">
				<div class="card-body">
						<div class="control-group">	
						<?php if (pm_count($meta_data) > 0) : ?>
						<div class="row">
							<div class="col-md-3"><strong>Name</strong></div>
							<div class="col-md-9"><strong>Value</strong></div>
						</div>
						<?php foreach ($_POST['meta'] as $meta_id => $meta) : 
									$meta['meta_key'] = $meta['key'];
									$meta['meta_value'] = $meta['value'];
									
									echo admin_custom_fields_row($meta_id, $meta);
								endforeach;
							endif; ?>
						</div>

						<?php echo admin_custom_fields_add_form(0, IS_VIDEO); ?>
				</div>
			</div>
		</div><!--.card-->


	</div><!--. col-md-9 main-->
	<div class="col-sm-12 col-md-3">
		<div class="card">
			<div class="card-header bg-white header-elements-inline">
				<h6 class="card-title font-weight-semibold">Thumbnail</h6>
				<div class="header-elements">
					<div class="list-icons">
						<span class="fileinput-button">
							<input type="file" name="file" id="upload-video-image-btn" class="file-input form-control form-control-sm alpha-grey" data-show-caption="false" data-show-upload="false" data-browse-icon="<i class='icon-upload4 mr-2'></i>" data-browse-label="Update" data-browse-class="btn btn-link btn-sm text-default font-weight-semibold" data-remove-class="btn btn-light btn-sm" data-show-remove="false" data-show-preview="false" />
						</span>
					</div>
				</div>
			</div>
			<div class="card-body upload-file-dropzone" id="video-thumb-dropzone">
				<div id="video-thumb-container">
					<?php
					$inputs['yt_thumb'] = ( ! empty($inputs['yt_thumb_local']) ? $inputs['yt_thumb'] = $inputs['yt_thumb_local'] : $inputs['yt_thumb'] = $inputs['yt_thumb']);
					if (empty($inputs['yt_thumb'])) : ?>

					<a href="#" id="show-thumb" data-toggle="collapse" data-target="#show-opt-thumb" rel="tooltip" title="Change the thumbnail URL"></a>

					<div class="d-block justify-content-end text-center rounded bt-slate alpha-slate" style="min-height: 150px;">
						<div>
							<i class="icon-image2 icon-3x text-default border-slate alpha-slate p-3 mt-1 mt-1"></i>
							<h5>No thumbnail</h5>
						</div>
					</div>
					<?php else : ?>
					<a href="#" id="show-thumb" data-toggle="collapse" data-target="#show-opt-thumb" rel="tooltip" title="Click here to change the thumbnail URL"><img src="<?php echo $inputs['yt_thumb']; ?>?cache_buster=<?php echo $time_now;?>" class="img-fluid" /></a>
					<?php endif; ?>
				</div>

				<div id="show-opt-thumb" class="collapse mt-3 p-3">
					<div class="input-group">
					<input type="text" name="yt_thumb" value="<?php echo $inputs['yt_thumb']; ?>" class="form-control col-md-10" placeholder="http://" /> <span class="input-group-text bg-transparent border-0"><i class="mi-info-outline" rel="tooltip" data-position="top" title="The thumbnail will refresh after you save the form."></i></span>
					</div>
				</div>
			</div>
		</div><!--.card-->

		<div class="card">
			<div class="card-header bg-white header-elements-inline">
				<h6 class="card-title font-weight-semibold">Category</h6>
				<div class="header-elements">
					<div class="list-icons">
						<a href="#" id="inline_add_new_category" class="btn btn-sm btn-link text-default text-uppercase font-weight-semibold" data-popup="tooltip" data-html="true" data-original-title="Create a new category" /><i class="mi-control-point"></i> Add</a>
					</div>
				</div>
			</div>
			<div class="card-body">
				<div id="inline_add_new_category_form" class="collapse border-grey border-bottom pb-3 mb-3">
					<span id="add_category_response"></span>
					<input name="add_category_name" type="text" placeholder="Category name" id="add_category_name" class="form-control mb-1" />
					<input name="add_category_slug" type="text" placeholder="Slug" class="form-control mb-1" data-popup="tooltip" data-html="true" data-original-title="Slugs are used in the URL (e.g. http://example.com/category/<strong>slug</strong>/) and can only contain numbers, letters, dashes and underscores." />
					<label class="text-muted">Create in (<em>optional</em>)</label>
					<?php 
						$categories_dropdown_options = array(
												'first_option_text' => '&ndash; Parent Category &ndash;', 
												'first_option_value' => '-1',
												'attr_name' => 'add_category_parent_id',
												'attr_id' => '',
												'attr_class' => 'custom-select mb-1',
												'select_all_option' => true,
												'spacer' => '&mdash;'
												);
						echo categories_dropdown($categories_dropdown_options); 
					?>
					<button name="add_category_submit_btn" value="Add category" class="btn btn-sm btn-success" />Create Category</button>
					<input type="hidden" name="categories_old" class="form-control" value="<?php echo $video_details['category'];?>"  />
				</div>
					<?php 
					$categories_dropdown_options = array(
													'attr_name' => 'category[]',
													'attr_id' => 'main_select_category',
													'attr_class' => 'category_dropdown custom-select mb-1 form-required',
													'select_all_option' => false,
													'spacer' => '&mdash;',
													'selected' => explode(',', $video_details['category']),
													'other_attr' => 'multiple="multiple"'
													);
					echo categories_dropdown($categories_dropdown_options);
					?>
			</div>
		</div><!--.card-->
		<div class="card">
			<div class="card-header bg-white header-elements-inline header-toggles" data-target="#cardSubtitles" data-toggle="collapse" aria-expanded="false" aria-controls="cardSubtitles">
				<h6 class="card-title font-weight-semibold">Video Subtitles</h6>
				<div class="header-elements">
					<div class="list-icons">
						<i class="mi-info-outline" rel="popover" data-trigger="hover" data-animation="true" title="Subtitles" data-content="Select the language you intend to assign a subtitle file for and then click the 'Upload' button. You can also replace or delete existing subtitles in the same manner. If you don't see the 'Delete' link for a subtitle, simply refresh this page."></i>
						<a href="#" data-target="#cardSubtitles" data-toggle="collapse" aria-expanded="false" aria-controls="cardSubtitles" class="text-default collapsed"><i class="icon-arrow-up12"></i></a>
					</div>
				</div>
			</div>
			<div class="collapse" id="cardSubtitles">
				<div class="card-body">
					<div class="text-muted">
						Subtitles are not possible for live streams.
					</div>
				</div>
			</div>
		</div><!--.card-->

	</div><!--. col-md-3 sidebar-->
</div><!--.row-->

<input type="hidden" name="language" value="1" />
<input type="hidden" name="source_id" value="0" />
<input type="hidden" name="age_verification" value="0" />
<input type="hidden" name="upload-type" value="" /> 
<input type="hidden" name="p" value="upload" /> 
<input type="hidden" name="do" value="upload-image" />
<input type="hidden" name="video_type" value="<?php echo IS_VIDEO; ?>" /> 

<div id="stack-controls-disabled" class="list-controls">
	<div class="float-right">
		<a href="videos.php" class="btn btn-sm btn-outline alpha-grey text-grey-400 border-transparent border-2 pm-show-loader">Cancel</a>
		<button type="submit" name="submit" value="Submit" class="btn btn-sm btn-outline alpha-success text-success-400 border-success-400 border-2"><i class="mi-check"></i> Save</button>
	</div>
</div><!-- #list-controls -->


</form>

<?php
	}	//	endif step == 2
	else if ($step == 3)
	{
		echo pm_alert_success($return_msg);

		echo '<br />';
		echo '<div class="btn-group"><input name="embed_new" type="button" value="&larr; Add a new stream" onClick="location.href=\'add-video-stream.php\'" class="btn btn-sm" />';
		echo '<input name="add_new" type="button" value="Add / upload new video" onClick="location.href=\'add-video.php?step=1\'" class="btn btn-sm" />';
		echo '<input name="import_new" type="button" value="Import Videos" onClick="location.href=\'import.php\'" class="btn btn-sm" />';
		echo '</div>';
	}
?>
</div>
<!-- /content area -->
<?php
include('footer.php');