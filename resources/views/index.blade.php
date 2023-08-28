<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

    <script type="text/javascript" src="https://cdn.datatables.net/1.11.2/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.2/css/jquery.dataTables.min.css">

    <script src="https://cdn.jsdelivr.net/npm/easy-responsive-tabs@0.0.2/js/easyResponsiveTabs.js"></script>
    <link rel="stylesheet" href="{{ asset('css/easy-responsive-tabs.css') }}" class="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.8.0/jszip.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.8.0/xlsx.js"></script>
    <style>

        #pageloader{
      background: rgba( 255, 255, 255, 0.8 );
          display:none;
      height: 100%;
      position: fixed;
      width: 100%;
      z-index: 9999;
      }
      #pageloader img{
          position: absolute;
          top: 36%;
          left: 44%;
          transform: translate(-50%, -50%);
      }
      .actionIcon:after {
        content: '\2807';
        }
      .table-wrapper {
          background: #fff;
          padding: 20px;
          box-shadow: 0 1px 1px rgba(0,0,0,.05);
      }
      .tableBooking{
          border-collapse: collapse;

          overflow-x: auto;
          white-space: nowrap;
      }
      .table-title {
          font-size: 15px;
          padding-bottom: 10px;
          margin: 0 0 10px;
          min-height: 45px;

      }
      .table-title h2 {
          margin: 5px 0 0;
          font-size: 24px;
          text-align: start !important;
      }
      .table-title select {
          border-color: rgb(104, 104, 104);
          border-width: 0 0 1px 0;
          padding: 3px 10px 3px 5px;
          margin: 0 5px;
      }
      .table-title .show-entries {
          margin-top: 7px;
      }
      table.table tr th, table.table tr td {
          border-color: #e9e9e9;
      }
      table.table th i {
          font-size: 13px;
          margin: 0 5px;
          cursor: pointer;
      }
      table.table td:last-child {
          width: 130px;
      }
      table.table td a {
          color: #a0a5b1;
          display: inline-block;
          margin: 0 5px;
      }
      table.table td a.view {
          color: #03A9F4;
      }
      table.table td a.edit {
          color: #FFC107;
      }
      table.table td a.delete {
          color: #E34724;
      }
      table.table td i {
          font-size: 19px;
      }
      #consignee_number_header{
          display:none;
      }
      .toast-success{
        font-size:15px;
      }
      .toast-error{
        font-size:15px;
      }
      .form-control{
        width: -moz-fit-content;
      }
      .card-body2{
        margin-bottom: 27px;
        margin-top: 29px;
      }
      .alert-danger{
        display: flex;
        justify-content: center;
      }

      </style>
</head>
<body>
@extends('shopify-app::layouts.default')
@section('script')
    @parent
        <script>
            var AppBridge = window['app-birdge'];
            var actions =  AppBridge.actions;
            var TitleBar = actions.TitleBar;
            var Button = actions.Button
            var Redirect = actions.Redirect;
            var titleBarOptions = {
                title:'Hello world ',
            };
            var myTitleBar = TitleBar.create(app, titleBarOptions);
        </script>
@endsection
@section('content')
    <div id="container">
      <div id="pageloader">
          <img src="{{ asset('loader.gif') }}" alt="processing..." />
      </div>

      <div id="parentHorizontalTab">
          <ul class="resp-tabs-list hor_1">
              <li>File Upload</li>
              <li>Errors Table</li>

          </ul>
          <div class="resp-tabs-container hor_1" id="clickTab" style="overflow-x: auto;">
              {{-- first Tab --}}
              <div>
              @if(isset($msg))
                    <div class="alert alert-danger" role="alert">
                    <span>File contains {{$count}} rows.{{$msg}}<span>
                    </div>
                @endif
                @if(isset($msg2))
                    <div class="alert alert-danger" role="alert">
                    <span>{{$msg2}}<span>
                    </div>
                @endif
                @if(isset($msg3))
                    <div class="alert alert-danger" role="alert">
                    <span>{{$msg3}}<span>
                    </div>
                @endif
                  <div class="row">
                      <div class="col-lg-4 col-md-4">
                          <div class="card">
                          <div class="card-header" >File Upload</div>
                              <div class="card-body">

                              <form action="{{ route('import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                           <div class="form-group">
                                          <label>Select File for Upload</label>
                                          <input type="file" name="file" id="FilUploader">
                                          <input type="submit" class="btn btn-success" value="Upload" onclick="loader()" style="margin-top: 5px;" disabled>

                                           </div>
                                </form>
                              </div>
                          </div>
                      </div>
                      <!-- <div class="col-lg-1 col-md-1"></div> -->
                      <div class="col-lg-7 col-md-7">
                      <div class="card">
                          <div class="card-header" >Instructions</div>
                            <div  class="card-body2">
                              <ul>
                                  <li>Please upload a file with extension .csv .xls .xlsx</li>
                                  <li>After uploading your file this app will create costumers on your shopify store</li>
                                  <li>Check the second tab for errors that were encountered during file upload</li>
                                </ul>


                            </div>
                          </div>
                      </div>
                  </div>
              </div>
              {{-- Second Tab --}}
              <div>
                  <div class="table-wrapper">
                      <div class="table-title">
                          <div class="row">
                              <div class="col-lg-12 col-md-12 col-sm-12">
                                  <h2 class="text-center">ERRORS TABLE</h2>
                              </div>
                          </div>
                      </div>

                      <table id="riderTableBooking" class="table table-bordered tableBooking" cellspacing="0" >
                          <thead>
                              <tr>
                                <th scope="col">User ID</th>
                                <th scope="col">Costumer ID</th>
                                <th scope="col">Errors</th>
                                <th scope="col">Created at</th>

                              </tr>
                            </thead>
                            <tbody>
                              @if(isset($error))
                              @foreach($error as $key=>$item)
                                    <tr >
                                        <td>{{$item['User_id']}}</td>
                                        <td>{{$item['costumer_id']}}</td>
                                        <td>{{$item['errors']}}</td>
                                        <td>{{$item['created_at']}}</td>
                                    </tr>
                              @endforeach
                              @endif
                            </tbody>
                          </table>
                    </div>
          </div>
          </div>
      </div>
    </div>
<script type="text/javascript">

  function  loader(){
      var loader = document.getElementById('pageloader').style.display = 'block';
  }


  //file extension check
  $("#FilUploader").change(function () {
      var fileExtension = ['csv', 'xls', 'xlsx'];
      if ($.inArray($(this).val().split('.').pop().toLowerCase(), fileExtension) == -1) {
          document.getElementById('pageloader').style.display = 'none';
          toastr.error("Please uplooad a file with extension : "+fileExtension.join(', '));
          $('#FilUploader').val("");
      }

  });

  //enable upload button if file extension matches

  $('input:file').on("change", function() {
    $('input:submit').prop('disabled', !$(this).val());
  });

//data shown on different tabs
  $(document).ready(function() {

            $('#parentHorizontalTab').easyResponsiveTabs({
                type: 'default',
                width: 'auto', //auto or any width like 600px
                fit: false, // 100% fit in a container
                tabidentify: 'hor_1', // The tab groups identifier
                activate: function(event) { // Callback function if tab is switched
                    var $tab = $(this);
                    var $info = $('#nested-tabInfo');
                    var $name = $('span', $info);
                    $name.text($tab.text());
                    $info.show();
                }
            });


        });


</script>
<!-- Backend Hit -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  @endsection
</body>
</html>
