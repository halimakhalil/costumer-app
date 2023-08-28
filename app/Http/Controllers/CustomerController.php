<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use Carbon\Carbon;
use Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Input;
use App\Imports\BulkImport;
use App\Jobs\SyncProducts;
use App\Models\error;

class CustomerController extends Controller
{
    public function home()
    {
        return view('index');
    }

    public function import(Request $request)
    {
        $import = (new BulkImport)->toArray($request->file);
        foreach ($import as $i) {
            foreach ($i as $key => $item) {
                if(isset($item['phone'], $item['user_email'], $item['first_name'], $item['last_name'], $item['roles'], $item['shipping_country'], $item['shipping_state'], $item['billing_phone'], $item['shipping_postcode'], $item['shipping_address_1'], $item['shipping_city'])){
                    {
                        (new BulkImport)->import(request()->file('file'), null, \Maatwebsite\Excel\Excel::CSV);
                        $error = error::latest('updated_at')->get();
                        return view('index',compact('error'));
                    }

                  }

                else{
                    $msg3 = 'File headers not matching';
                    return view('index', compact('msg3'));
                }

            }
        }

    }
}
