<?=$header?>
<?=$sidebar?>
<!-- ============================================================== -->
<!-- Bread crumb and right sidebar toggle -->
<!-- ============================================================== -->

<style>
    .pcontainer {
        width: 90%;
        background-color: rgb(214, 207, 207);
        height: 10px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        padding: 2px;
    }
    .text {
        color: black;
        font-weight:bold;
    }
    .progress {
        height: 60%;
        background-color: #1e88e5!important;
        width: 0%;
        border-radius: 10px;
        transition: all 1s;
    }
</style>
<div class="row page-titles">
<div class="col-md-5 col-12 align-self-center">
		<h3 class="text-themecolor mb-0">Files</h3>
		<ol class="breadcrumb mb-0">
			<li class="breadcrumb-item"><a href="<?php echo base_url();?>">Dashboard</a></li>
			<li class="breadcrumb-item"><a href="<?php echo base_url('files/filesList');?>">Files</a></li>
			<li class="breadcrumb-item active">Add Files</li>
		</ol>
	</div>
	<!--div class="col-md-7 col-12 align-self-center d-none d-md-block">
		<div class="d-flex mt-2 justify-content-end">
			<a href="#" class="btn btn-info"><i class="fas fa-arrow-circle-left"></i> Back</a>
		</div>
	</div-->
</div>
<!-- ============================================================== -->
<!-- End Bread crumb and right sidebar toggle -->
<!-- ============================================================== -->
<!-- ============================================================== -->
<!-- Container fluid  -->
<!-- ============================================================== -->
<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card scroll-sidebar">
				<div class="card-header">
					<h4 class="card-title"> Add File</h4>
				</div>
				<div class="card-body">
					<form method="POST" class="form-material mt-4" id="frm" action="#" enctype="multipart/form-data">
						<div class="row">

                            <div class="col-4 col-md-4">
                                <div class="form-group">
                                    <label>Formats<font color="#FF0000">*</font></label>                                    
                                    <select id="format_id" class="form-control" name="format_id">
                                        <option value="">Select Format</option>
                                        <?php if( $formats ){
                                            $id = $name = "";
                                            foreach($formats as $r){ 
                                                $id = encode_it($r->id);
                                                $name = trim(htmlspecialchars($r->name));
                                                $name = ucfirst($name);
                                            ?>													
                                                <option value="<?php echo $id;?>"><?php echo $name;?></option>
                                            <?php  
                                            } 
                                        }?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-4 col-md-4">
                                <div class="form-group">
                                    <label>Geners<font color="#FF0000">*</font></label>                                    
                                    <select id="geners_id" class="form-control" name="geners_id">
                                        <option value="">Select Geners</option>
                                        <?php if( $geners ){
                                            $id = $name = "";
                                            foreach($geners as $r){ 
                                                $id = encode_it($r->id);
                                                $name = trim(htmlspecialchars($r->name));
                                                $name = ucfirst($name);
                                            ?>													
                                                <option value="<?php echo $id;?>"><?php echo $name;?></option>
                                            <?php  
                                            } 
                                        }?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-4 col-md-4">
                                <div class="form-group">
                                    <label>Language<font color="#FF0000">*</font></label>                                    
                                    <select id="language_id" class="form-control" name="language_id">
                                        <option value="">Select Language</option>
                                        <?php if( $language ){
                                            $id = $name = "";
                                            foreach($language as $r){ 
                                                $id = encode_it($r->id);
                                                $name = trim(htmlspecialchars($r->name));
                                                $name = ucfirst($name);
                                            ?>													
                                                <option value="<?php echo $id;?>"><?php echo $name;?></option>
                                            <?php  
                                            } 
                                        }?>
                                    </select>
                                </div>
                            </div>

                            <!-- <div class="col-6 col-md-6">
                                <div class="form-group">
                                    <label>Category<font color="#FF0000">*</font></label>                                    
                                    <select id="cat_id" class="form-control" name="cat_id">
                                        <option value="">Select Category</option>
                                        <?php if( $category ){
                                            $id = $name = "";
                                            foreach($category as $r){ 
                                                $id = encode_it($r->id);
                                                $name = trim(htmlspecialchars($r->name));
                                                $name = ucfirst($name);
                                            ?>													
                                                <option value="<?php echo $id;?>"><?php echo $name;?></option>
                                            <?php  
                                            } 
                                        }?>
                                    </select>
                                </div>
                            </div> -->

                            <div class="col-12 ">
                                <div class="form-group">
                                    <label>Select Video <font color="#FF0000">*</font></label>
                                    <div>
                                        <span class="input-note">
                                            <strong>Note: </strong><br>
                                            File size should be max 1GB <br>
                                            <!-- Allowed video types (png,jpg,jpeg,webp) -->
                                        </span>
                                    </div>
                                    <br>
                                    <input type="file" name="video" id="video" accept="video/*" required=""><br>
                                </div>
                            </div>   
                            
                            <div class="col-12 uploadProgress">
                                <div class="pcontainer">
                                    <div class="progress" id="progress"></div>
                                </div>
                                <div class="text" id="text"></div>  
                            </div>
								
							<div class="col-md-12" align="center">
								<?php 
								$csrf = array('name' => $this->security->get_csrf_token_name(),'hash' => $this->security->get_csrf_hash());
								?>
								<input type="hidden" name="<?=$csrf['name'];?>" class="csrf_tkn" value="<?=$csrf['hash'];?>" />
								<div class="col-12">
									<div class="form-group" >
										<button tabindex="14" type="submit" class="btn btn-rounded btn-success"> <i class="mdi mdi-check"></i> Save</button>
										<a href="<?php echo base_url('files');?>" tabindex="15" class="btn btn-rounded btn-danger btn-success"> <i class="fa fa-times"></i> Cancel</a>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>                            
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">

// let cnt = 0;
// let per = 0;
// red = setInterval(() => {
//     let bar = document.querySelector(".progress");
//     let percentage = setInterval(() => {
//     per += 1;
//     if (per >= cnt) clearInterval(percentage);
//     document.querySelector(".text").innerHTML = `<p>${per}%</p>`;
//     }, 100);
//     cnt += 10;

//     if (cnt == 100) clearInterval(red);
//     bar.style.width = cnt + "%";
//     console.log(cnt);
// }, 1000);


var file_id = 0
var uploadId = ''
var partNumber = 1
var parts = []
// console.log("parts : ",parts)
var chunks = 0
var chunksUploaded = 0
var folder = ''
let percent = 0
let step = 0
let bar = document.querySelector(".progress");

var payload = []

$(document).ready(function() {

    $('#cat_id').select2({placeholder: 'Select Category'});

    $('input[name="minutes"]').keyup(function(e){
        if (/\D/g.test(this.value)){
            // Filter non-digits from input value.
            this.value = this.value.replace(/\D/g, '');
        }
    });
	
	$('#frm').bootstrapValidator({
		excluded: [':disabled', ':hidden', ':not(:visible)'],
		fields: {
			
            'format_id': {
				validators: {
					notEmpty: {
						message: 'Please select format.'
					},
				}
			},
            'geners_id': {
				validators: {
					notEmpty: {
						message: 'Please select genre.'
					},
				}
			},
            'language_id': {
				validators: {
					notEmpty: {
						message: 'Please select language.'
					},
				}
			},
            // 'cat_id': {
			// 	validators: {
			// 		notEmpty: {
			// 			message: 'Please select category.'
			// 		},
			// 	}
			// },
            'video': {
				validators: {
					notEmpty: {
						message: 'Please select video.'
					}
				}
			},
		}
	}).on('success.form.bv', function(e) {
		e.preventDefault();

        let size = document.getElementById("video").files[0].size
        size = ((size/1024)/1024)

        // limit set to 2GB
        if( size > 2048 ){
            Swal.fire("Please select file below 1 GB")
        }else{
            console.log("Size in MB : ",size)
            // console.log($('#frm #video').val())
            uploadFile()
        }
	});
	
	var inputs = $(':input').keypress(function(e){ 
		if (e.which == 13) {
		   e.preventDefault();
		   var nextInput = inputs.get(inputs.index(this) + 1);
		   if (nextInput) {
			  nextInput.focus();
		   }
		}
	});
});	

function uploadFile(){
    var file = $('#video')[0].files
    // console.log(" file : ",file)
    if( file.length ){
        // console.log($('#f')[0].files[0])

        // Swal.fire({
        //     allowOutsideClick: false,
        //     html : '<i class="fas fa-spinner fa-spin"></i> Uploading please wait...',
        //     buttons: false,
        //     showConfirmButton: false,
        // })
        
        $.ajax({
            url: '<?php echo base_url('files/initiateFileUpload');?>',
            type: "post",
            // data: {cat_id:$.trim($('#cat_id').val()),fileName:file[0].name} ,
            data: {format_id:$.trim($('#format_id').val()),geners_id:$.trim($('#geners_id').val()),language_id:$.trim($('#language_id').val()),fileName:file[0].name} ,
            dataType:"json",
            success: function (response) {
                // console.log(response)
                if( response.status == true ){
                    if( response.uploadId != '' ){
                        file_id = response.file_id
                        let status = processFile(response.uploadId)
                        // console.log("Upload Status : ",status)
                    }  
                }else{
                    Swal.fire(response.message)
                }              
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    }else{
        Swal.fire("Please upload valid file")
    }
    // return false
}

function processFile(uploadId = '') {
    if( uploadId != '' ){
        var file = $('#video')[0].files[0];
        var size = file.size;
        var sliceSize = 5 * 1024 * 1024;
        var start = 0;
        chunks = Math.ceil(size/sliceSize)
        // console.log("Chunks : ",chunks)
        // console.log("Size : ",size)

        step = 100 / chunks
        // console.log("step : ",step)

        setTimeout(loop, 1);
        function loop() {
            var end = start + sliceSize;
            if (size - end < 0) {
                end = size;
            }
            
            var s = slice(file, start, end);
            // console.log("Sliced File : ",s)
            send(s, start, end, uploadId);
            if (end < size) {
                start += sliceSize;
                setTimeout(loop, 1);
            }
            // console.log("Parts : ",parts)
        }
    }
}

function send(piece, start, end, uploadId) {
    // console.log("Part : ",partNumber)
    var formdata = new FormData();            
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo base_url('files/uploadChunks');?>', true);

    formdata.append('start', start);
    formdata.append('end', end);
    formdata.append('file', piece);
    formdata.append('uploadId', uploadId);
    formdata.append('file_id', file_id);
    formdata.append('fileName', $('#video')[0].files[0].name);
    formdata.append('partNumber', partNumber);
    formdata.append('folder', folder);

    xhr.onload = function() {
        // console.log("Response : ",this)
        // let result = JSON.parse(this.responseText);
        // console.log("Response : ",result)
        // parts.push([parseInt(myObj.PartNumber),myObj.ETag])

        percent = percent + step
        percent = Math.floor(percent)
        console.log("percent : ",percent)
        if( percent >= 90 ){
            document.querySelector(".text").innerHTML = `<p>90 %</p>`;
            bar.style.width =  + "90 % ";
        }else{
            document.querySelector(".text").innerHTML = `<p>${percent} %</p>`;
            bar.style.width = percent + "%";
        }
        

        chunksUploaded++
        if( chunksUploaded == chunks ){
            console.log("Chunk uploaded successfully")
            generateFile()
        }

    };
    xhr.send(formdata);
    partNumber++
}

function generateFile(){
    if( chunks == chunksUploaded && file_id != 0  ){
        console.log("Generate File")
        // console.log("parts : ",parts)
        $.ajax({
            url: '<?php echo base_url('files/completeUpload');?>',
            type: "post",
            data: {file_id:file_id} ,
            dataType:"json",
            success: function (response) {
                // console.log(response)  
                if( response.status == true ){
                    partNumber = 1  
                    chunks = chunksUploaded = 0

                    $('#progress').css('width','100%')
                    $('#text').html('<p>100 %</p>')
                    
                    Swal.fire(response.message)
                    window.location.href='<?php echo base_url('files/filesList');?>'
                }else{
                    Swal.fire(response.message)
                }                                 
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
                partNumber = 1
                chunks = chunksUploaded = 0
            }
        });

    }
}


function slice(file, start, end) {
    var slice = file.mozSlice ? file.mozSlice :
                file.webkitSlice ? file.webkitSlice :
                file.slice ? file.slice : noop;

    return slice.bind(file)(start, end);
}

function noop() {

}

</script>
<?=$footer?>