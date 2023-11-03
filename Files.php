<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require '../aws/aws-autoloader.php';
use Aws\S3\S3Client;
use Aws\ElasticTranscoder\ElasticTranscoderClient;

class Files extends CI_Controller {

	public function __construct() {
		parent::__construct();
		
		$this->user_id = check_login();
		if(!$this->user_id)
		{
			redirect("account/login");
		}
		
		$this->load->library('encrypt');
		$this->load->config('media');
		$this->load->model('vendor_db');
		$this->load->model('Master_db');

		$this->data['detail'] = '';

		$this->data['header'] = $this->load->view('includes/header',array(),true);
		$this->data['sidebar'] = $this->load->view('includes/sidebar',array(),true);
		$this->data['footer'] = $this->load->view('includes/footer',array(),true);
		$this->data['user_id'] = $this->session->userdata('is_login');

        $this->data['aws_config'] = array(
            'version' => 'latest',
            'region' => 'ap-south-1',
            'credentials' => array(
                'key'    => 'AKIASVNYKMBCKAK2GRGQ',
                'secret' => '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',
            )
        );

        // $this->data['bucket'] = 'swarnagowriinv';
        // $this->data['cloudfront'] = 'https://d3birbpccr6lj2.cloudfront.net/';
        // $this->data['pipelineId'] = '1697697863486-seiywp';
        // $this->data['presetId'] = '1351620000001-200010';

        $this->data['bucket'] = 'brainsharetv';
        $this->data['cloudfront'] = 'https://d3hnz9gqcoaclm.cloudfront.net/';
        $this->data['pipelineId'] = '1698256675736-s4blbf';
        $this->data['presetId'] = '1351620000001-200010';
	}	

	public function index(){
		$this->filesList();
	}

	// Segment Functions start
	public function filesList()
	{	
		$this->load->view('files/files_list',$this->data);
	}

    public function filesTableList(){
		$det = $this->data['detail'];
        $data_list = $this->vendor_db->getFilesList($det);
        //echo $this->db->last_query();
        
        $data = array();
        $i = $_POST["start"]+1;
        
        foreach($data_list as $row)
        {
            $sub_array = array();
            $sub_array[] = $i++;
        
            $sub_array[] = $row->title;
            $sub_array[] = $row->lang_name;
            $sub_array[] = $row->title;
			$sub_array[] = $row->gname;
            $sub_array[] = ($row->status==1)?'Active':'Inactive';
            $sub_array[] = ($row->s3_status==1)?'Pending':(($row->s3_status==2)?'Completed':'Failed');

            $transcode_status = 'Pending';
            switch($row->transcode_status){
                case 1:$transcode_status='Processing';break;
                case 2:$transcode_status='Completed';break;
                case 3:$transcode_status='Error';break;
                default:$transcode_status='Pending';break;
            }

            $sub_array[] = $transcode_status;
            $sub_array[] = $row->hls_url;
            $sub_array[] = $row->created_at;

            if( $row->transcode_status == 0 && $row->s3_status == 2 ){
                $sub_array[] = "<button title='Start Conversion' onclick='convertVideo(".$row->id.")' class='btn btn-info btn-sm'><i class='fas fa-check'></i> Convert To HLS</button>&nbsp;";
            }else if( $row->transcode_status == 1 ){
                $sub_array[] = "<button title='Check Conversion Status' onclick='convertVideoStatus(".$row->id.")' class='btn btn-info btn-sm'><i class='fas fa-check'></i> HLS Status</button>&nbsp;";
            }else{
                $sub_array[] = '';
            }
            

            $data[] = $sub_array;
            
        }
        $_POST["length"] = -1;
        $members = $this->vendor_db->getFilesList($det);
        
        $total = count($members);
        $output = array(
            "draw"              =>  intval($_POST["draw"]),
            "recordsTotal"      =>  $total,
            "recordsFiltered"   =>  $total,//$this->master_db->get_filtered_data("guards")
            "data"              =>  $data
        );
        echo json_encode($output);
	}

    // Upload file
	public function upload()
	{	
        $query = "select id,title as name from category where status=1";
		$this->data['category'] = $this->main_model->run_manual_query_result($query);

        $query = "select id,title as name from geners where status=1";
		$this->data['geners'] = $this->main_model->run_manual_query_result($query);

        $query = "select pk_formats as id,name from formats where status=1";
		$this->data['formats'] = $this->main_model->run_manual_query_result($query);

        $query = "select id,lang_name as name from language where status=1";
		$this->data['language'] = $this->main_model->run_manual_query_result($query);

        // echo '<pre>';print_r($data['category']);exit;
		$this->load->view('files/upload',$this->data);
	}

	public function initiateFileUpload(){

        // echo '<pre>';print_r($_POST);exit;
        if( !empty(trim($_POST['format_id'])) && !empty(trim($_POST['geners_id'])) && !empty(trim($_POST['language_id'])) && !empty(trim($_POST['fileName'])) ){

            $format_id = trim($_POST['format_id']);
            $geners_id = trim($_POST['geners_id']);
            $language_id = trim($_POST['language_id']);

            $query = "select pk_formats from formats where status=1 and pk_formats = ".$format_id;
		    $checkCategory = $this->main_model->run_manual_query_result($query);
            if( count($checkCategory) ){

                $params = array(
                    'format_id'     =>  $format_id,
                    'genres_id'     =>  $geners_id,
                    'language_id'   =>  $language_id,
                );

                $vpath = $this->get_vfpath($params);
                // echo $vpath;
                // echo '<pre>';print_r($params);exit;

                $s3 = S3Client::factory($this->data['aws_config']);
                $keyname = trim($_POST['fileName']);
                $result = $s3->createMultipartUpload([
                    'Bucket'       => $this->data['bucket'],
                    // 'Key'          => 'videos/'.$keyname,
                    'Key'          => $vpath,
                    // 'StorageClass' => 'REDUCED_REDUNDANCY',
                    'StorageClass' => 'STANDARD',
                    'Metadata'     => [
                        'param1' => 'value 1',
                        'param2' => 'value 2',
                        'param3' => 'value 3'
                    ],
                    'ACL'      =>   'public-read'
                ]);

                if( isset($result['UploadId']) ){
                    $insert = array(
                        // 'cat_id'    =>  $cat_id,
                        'cat_id'    =>  0,
                        'format_id' =>  $format_id,
                        'genres_id' =>  $geners_id,
                        'path'      =>  $vpath,
                        'cpath'     =>  $vpath,
                        'language_id' =>  $language_id,
                        'file_name' =>  $keyname,
                        'file_size' =>  '',
                        'upload_id' =>  $result['UploadId'],
                        's3_url'    =>  '',
                        'cloud_url' =>  '',
                        'status'    =>  1,
                        's3_status' =>  1,
                        'transcode_status'  =>  0,
                        'hls_url'   =>  '',
                        'created_by'=>  $this->data['user_id'],			
				        'created_at'=> date('Y-m-d H:i:s'),
				        'updated_by'=> 0,
                        'updated_at'=> date('Y-m-d H:i:s')
                    );
                    $file_id = $this->Master_db->insertRecord("files",$insert);
                    echo json_encode(array('status'=>true,'uploadId'=>$result['UploadId'],'file_id'=>$file_id,'message'=>'Success'));    
                }else{
                    echo json_encode(array('status'=>false,'uploadId'=>'','message'=>'Something went wrong!!!'));
                }

            }else{
                echo json_encode(array('status'=>false,'uploadId'=>'','message'=>'Category not found!!!'));
            }
            // echo '<pre>';print_r($checkCategory);exit;
        }else{
            echo json_encode(array('status'=>false,'uploadId'=>''));
        }
        // echo '<pre>';print_r($this->data['aws_config']);exit;
    }

    public function uploadChunks(){

        // echo phpinfo();exit;
        // echo '<pre>';print_r($_POST);print_r($_FILES);exit;
        if( !empty($_POST['uploadId']) && !empty($_POST['file_id']) && !empty($_POST['partNumber']) && !empty($_FILES['file']) ){
            
            $uploadId = trim($_POST['uploadId']);
            $file_id = trim($_POST['file_id']);
            $partNumber = trim($_POST['partNumber']);
            $keyname = $fileName = trim($_POST['fileName']);

            $query = "select id,path from files where path != '' and id = ".$file_id;
		    $checkFile = $this->main_model->run_manual_query_result($query);

            // $serverFileName = '../uploads/'.rand().$fileName;
            $filename = trim($_FILES['file']['tmp_name']);
            // move_uploaded_file($_FILES['file']['tmp_name'],$serverFileName);
            // copy($_FILES['file']['tmp_name'],$serverFileName);
            // unlink($filename);
            // echo '<pre>';print_r($_FILES['file']);exit;

            $s3 = S3Client::factory($this->data['aws_config']);

            try {
                // echo $filename.'<br>';
                $file = fopen($filename, 'r');
                
                // while (!feof($file)) {
                    $result = $s3->uploadPart([
                        'Bucket'     => $this->data['bucket'],
                        // 'Key'        => 'videos/'.$keyname,
                        'Key'        => $checkFile[0]->path,
                        'UploadId'   => trim($_POST['uploadId']),
                        'PartNumber' => $partNumber,
                        'Body'       => fread($file, $_FILES['file']['size']),
                    ]);
                    // echo '<pre>';print_r($result);exit;

                    if( isset($result['ETag']) ){
                        $etag = str_replace('"','',$result['ETag']);

                        $insert = array(
                            'file_id'   =>  $file_id,
                            'partnumber'=>  $partNumber,
                            'etag'      =>  $etag,
                            'status'    =>  1,
                            'created_at'=> date('Y-m-d H:i:s'),
                        );
                        $this->Master_db->insertRecord("file_parts",$insert);

                        echo json_encode(array('PartNumber'=>$partNumber,'ETag'=>str_replace('"','',$result['ETag']),'status'=>true));
                    }
                // }
                fclose($file);
                if( file_exists($filename) ){
                    unlink($filename);
                }        
            } catch (S3Exception $e) {
                $result = $s3->abortMultipartUpload([
                    'Bucket'   => $bucket,
                    'Key'      => $keyname,
                    'UploadId' => $uploadId
                ]);
                echo json_encode(array('PartNumber'=>$partNumber,'ETag'=>'','status'=>false));
                // echo "Upload of {$filename} failed." . PHP_EOL;
            }
            
        }

    }

    public function completeUpload(){

        if( !empty($_POST['file_id']) ){
            $file_id = trim($_POST['file_id']);

            $query = "select * from files where status=1 and id = ".$file_id;
		    $file = $this->main_model->run_manual_query_result($query);
            // echo '<pre>';print_r($file);exit;

            $query = "select * from file_parts where status=1 and file_id = ".$file_id." order by partnumber asc";
		    $parts = $this->main_model->run_manual_query_result($query);
            
            if( count($file) && count($parts) ){

                $fileParts = array();
                for($p=0;$p<count($parts);$p++){
                    $fileParts[$parts[$p]->partnumber] = array(
                        'PartNumber'    =>  $parts[$p]->partnumber,
                        'ETag'          =>  $parts[$p]->etag
                    );
                }
                // echo '<pre>';print_r($fileParts);exit;
                $opath = explode('/',$file[0]->path);
                $opath[count($opath)-1] = '';

                // echo implode('/',$opath);exit;
                $s3 = S3Client::factory($this->data['aws_config']);
                $s3->putObject([
                    'Bucket' => $this->data['bucket'],
                    'Key' => implode('/',$opath).'index.html',
                    'SourceFile' => '/var/www/html/admin/uploads/index.html',
                    'ContentType' => 'text/html',
                    'ACL'         => 'public-read',
                ]);

                // echo $file[0]->path;exit;

                $result = $s3->completeMultipartUpload([
                    'Bucket'   => $this->data['bucket'],
                    // 'Key'      => 'videos/'.$file[0]->file_name,
                    'Key'      => $file[0]->path,
                    'UploadId' => $file[0]->upload_id,
                    'MultipartUpload'    => ['Parts'=>$fileParts],
                    // 'ACL'      =>   'public-read'
                ]);

                if( isset($result['Location']) && $result['Location'] != '' ){
                    $url = $result['Location'];

                    $cpath = str_replace('vfiles','cfiles',$file[0]->path);
                    $cpath = str_replace('mp4','m3u8',$file[0]->path);

                    $upData = array(
                        's3_status'  =>  2,
                        's3_url'     =>  $url,
                        // 'convert_path' => $cpath,
                        // 'cloud_url'  =>  $this->data['cloudfront'].'videos/'.$file[0]->file_name,
                        'cloud_url'  =>  $this->data['cloudfront'].$cpath,
                        'updated_by' =>  $this->data['user_id'],
                        'updated_at' =>  date('Y-m-d H:i:s')
                    );                    
                    $updated = $this->main_model->update_where("files",$upData,array('id'=>$file_id));
                    echo json_encode(array('status'=>true,'message'=>'File uploaded successfully.'));
                }else{
                    echo json_encode(array('status'=>false,'message'=>'Failed to upload.'));
                }
                // echo $url;                

            }else{
                echo json_encode(array('status'=>false,'message'=>'Failed to upload.'));
            }           

        }else{
            echo json_encode(array('status'=>false,'message'=>'Failed to upload.'));
        }

    }

    public function convertHls(){
        if( !empty($_GET['file_id']) ){
            $file_id = trim($_GET['file_id']);
            $query = "select * from files where status=1 and s3_status = 2 and id = ".$file_id;
		    $file = $this->main_model->run_manual_query_result($query);
            
            if( count($file) ){

                $cpath = $path = explode('/',$file[0]->path);                
                $cpath = str_replace('vfiles','cfiles',$file[0]->path);
                $cpath = str_replace('.mp4','.m3u8',$cpath);
                $cpath = explode('/',$cpath);
                $cpath[count($cpath)-1] = '';
                $cpath = implode('/',$cpath);
                // echo $file[0]->path.'<br>';
                // echo $cpath;exit;

                // $cpath = array_slice($cpath,0,count($cpath)-1);
                // $cpath[0] = 'cfiles';
                // $cpath = implode('/',$cpath).'/';
                // $cname = str_replace('.mp4','.m3u8',$path[count($path)-1]);
                // echo '<pre>';print_r($cpath);exit;

                $elasticTranscoder = ElasticTranscoderClient::factory($this->data['aws_config']);
                
                // Create transcode job
                // $keyName = explode('.',$file[0]->file_name)[0];
                // echo $path[count($path)-1];exit;
                $keyName = explode('.',$path[count($path)-1])[0];
                // echo $keyName;exit;
                $job = $elasticTranscoder->createJob(array(
                    'PipelineId' => $this->data['pipelineId'],
                
                    'OutputKeyPrefix' => $cpath,
                
                    'Input' => array(
                        // 'Key' => 'videos/'.$file[0]->file_name,
                        'Key' => $file[0]->path,
                        'FrameRate' => 'auto',
                        'Resolution' => 'auto',
                        'AspectRatio' => 'auto',
                        'Interlaced' => 'auto',
                        'Container' => 'auto',
                    ),
                
                    'Outputs' => array(
                        array(
                            'PresetId'  =>  $this->data['presetId'],
                            'Key'       =>  $keyName,
                            'Rotate'    =>  'auto',
                            'SegmentDuration' => '60',
                        ),
                    ),
            
                    'Playlists' => array(
                        array(
                            'Format'    =>  'HLSv3',
                            'Name'      =>  $keyName.'.m3u8',
                            // 'Name'      =>  $keyName,
                            // 'Name'      =>  'C',
                            'OutputKeys'=>  array(
                                $keyName
                            )
                        )
                    )
                ));

                // get the job data as array
                $jobData = $job->get('Job');

                // you can save the job ID somewhere, so you can check 
                // the status from time to time.
                $jobId = $jobData['Id'];

                $upData = array(
                    // 'cpath' => $cpath.$cname,
                    'transcode_status'  =>  1,
                    'job_id'     =>  $jobId,
                    'updated_by' =>  $this->data['user_id'],
                    'updated_at' =>  date('Y-m-d H:i:s')
                );                    
                $updated = $this->main_model->update_where("files",$upData,array('id'=>$file_id));
                // echo $jobId;exit;
                echo json_encode(array('status'=>true,'message'=>'File conversion started successfully.'));

            }else{
                echo json_encode(array('status'=>false,'message'=>'File not found.'));
            }
        }else{
            echo json_encode(array('status'=>false,'message'=>'Something went wrong.'));
        }
    }

    public function convertHlsStatus(){
        if( !empty($_GET['file_id']) ){
            $file_id = trim($_GET['file_id']);
            $query = "select * from files where status=1 and transcode_status = 1 and job_id != '' and id = ".$file_id;
            // $query = "select * from files where status=1 and id = ".$file_id;
		    $file = $this->main_model->run_manual_query_result($query);
            
            if( count($file) ){

                $elasticTranscoder = ElasticTranscoderClient::factory($this->data['aws_config']);

                // Create transcode job
                $keyName = explode('.',$file[0]->file_name)[0];
                $jobResult = $elasticTranscoder->readJob(array('Id' => $file[0]->job_id));
                // echo '<pre>';print_r($jobResult);exit;

                // Status list Submitted|Progressing|Complete|Canceled|Error
                $status = $jobResult->get('Job')['Status'];
                $transcode_status = 1;
                $message = '';
               
                $cpath = str_replace('vfiles','cfiles',$file[0]->path);
                $cpath = str_replace('.mp4','.m3u8',$cpath);

                $upData = array(
                    'transcode_status'  =>  $transcode_status,
                    'updated_by' =>  $this->data['user_id'],
                    'updated_at' =>  date('Y-m-d H:i:s')
                );

                if( $status == 'Complete' ){ 
                    $upData['transcode_status'] = 2; 
                    // $upData['hls_url'] = $this->data['cloudfront'].'convertedvideos/'.$keyName.'.m3u8';
                    $cpath = str_replace('vfiles','cfiles',$file[0]->path);
                    $cpath = str_replace('.mp4','.m3u8',$cpath);

                    $upData['cpath'] = $cpath;
                    $upData['hls_url'] = $this->data['cloudfront'].$cpath;
                    $message = 'File converted successfully.';
                }else if( $status == 'Canceled' || $status == 'Error' ){ 
                    $upData['transcode_status'] = 3;
                    $message = 'File conversion failed.';
                }else if( $status == 'Progressing' ){ 
                    $upData['transcode_status'] = 1;
                    $message = 'File conversion is in progress.';
                }
                // echo '<pre>';print_r($jobResult);exit;                
                $updated = $this->main_model->update_where("files",$upData,array('id'=>$file_id));
                echo json_encode(array('status'=>true,'message'=>$message));

            }else{
                echo json_encode(array('status'=>false,'message'=>'File not found.'));
            }
        }else{
            echo json_encode(array('status'=>false,'message'=>'Something went wrong.'));
        }
    }

    // Get video path
    function get_vfpath($params)
	{
		if(is_array($params))
		{
			$year = date('Y');		
			$format_id   = $params['format_id'];
			$language_id = $params['language_id'];
			$genres_id   = $params['genres_id'];
			
			$is_trailer  = isset($params['is_trailer'])?$params['is_trailer']:0;
			
			$format_prefix = "BS";
			$dir_path      = "vfiles";
			$extension     = ".mp4";
			$lang_name     = "other";
			$genre_name    = "other";
			$random_number = mt_rand(1000,9999);
			$mt_string     = preg_replace('/(0)\.(\d+) (\d+)/','$3$1$2',microtime());
			
			$where = array();
			$where['id'] = $language_id;
			$lan_res = $this->main_model->get_row_where("language",$where,"lang_name");
			if($lan_res)
			{
				$lang_name = strtolower(trim($lan_res->lang_name));
				$lang_name = preg_replace('/\s+/', ' ', $lang_name);
				$lang_name = str_replace(" ","_",$lang_name);
				$lang_name = preg_replace('/[^A-Za-z0-9_\-]/', '', $lang_name);
			}
			
			$where = array();
			$where['id'] = $genres_id;
			$gen_res = $this->main_model->get_row_where("geners",$where,"title");
			if($gen_res)
			{
				$genre_name = strtolower(trim($gen_res->title));
				$genre_name = preg_replace('/\s+/', ' ', $genre_name);
				$genre_name = str_replace(" ","_",$genre_name);
				$genre_name = preg_replace('/[^A-Za-z0-9_\-]/', '', $genre_name);
			}			
			
			switch($format_id)
			{
				case 2: 					
					if($is_trailer)
					{
						$format_prefix.="TVTRLR";
					}else{
						$format_prefix.="TVVF";
					}
					$dir_path.="/tvshows";
					break; 					
				case 3:
					if($is_trailer)
					{
						$format_prefix.="MVTRLR";
					}else{
						$format_prefix.="MVVF";
					}
					$dir_path.="/movies";
					break; 
			}
			
			$uid = $this->user_id;
			$uid = is_numeric($uid)?$uid:0;
			
			$file_name  = $format_prefix.$random_number.$uid.$mt_string.$extension;
			//$final_path = $dir_path."/".$year."/".$lang_name."/".$file_name;
			$final_path = $dir_path."/".$lang_name."/".$genre_name."/".$file_name;
			
			return $final_path;
		}else{
			return null;
		}
	}

}