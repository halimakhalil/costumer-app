<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\sync_products;
use App\Models\User;
use App\Models\General_Setting;
use App\Models\JobsExtension;

class SyncProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shop;

    // retry job after 10 seconds
    public $retryAfter = 10;

    // override the queue tries
    // configuration for this job
    public $tries = 4;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shop)
    {
        $this->shop = $shop;
    }

    public function retryAfter()
    {
        return 10;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info("Products Sync Triggered1!");

        $page_info      = '';
        $type = '';
        $userId         = $this->shop['id'];
        $last_page      = false;
        $validatedData  = array();
        $products       = DB::table('sync_products')
                             ->where('uid', $userId)
                             ->orderBy('id', 'desc')
                             ->first();

        // $GernalSettingDetail = General_Setting::select("user_id","api_user","api_token")
        //                                        ->where("user_id", $userId)
        //                                        ->first();

        \Log::info(json_decode( json_encode($products), true));
        if($products && $products->next_token != null && $products->type == "created"){
        \Log::info("1st");
            $productsShopify = $this->shop->api()->rest('GET', "/admin/api/2022-01/products.json",[ 'limit'=>250,'page_info'=>$products->next_token ]);
            $type = "created";

        }elseif($products && $products->next_token == null){
            $created_at = date_create($products->created_at);
            \Log::info("2nd");
            \Log::info(date_format($created_at,"Y-m-d\TH:i:sO"));
            $productsShopify = $this->shop->api()->rest('GET', "/admin/api/2022-01/products.json",[ 'limit'=>250,"updated_at_min" => date_format($created_at,"Y-m-d\TH:i:sO")]);
            $type = "updated";

        }elseif($products && $products->next_token != null && $products->type == "updated"){
            \Log::info("3nd");
            $productsShopify = $this->shop->api()->rest('GET', "/admin/api/2022-01/products.json",[ 'limit'=>250,'page_info'=>$products->next_token ]);
            $type = "updated";
        }else{
            \Log::info("4rd");
            $productsShopify = $this->shop->api()->rest('GET', "/admin/api/2022-01/products.json",[ 'limit'=>250]);
            $type = "created";
        }
        if($productsShopify['body']['container']['products'] != null)
        {
            $productsArray = array();
            $productsCount = 0;
            $productsApi = $productsShopify['body']['container']['products'];
            foreach($productsApi as $product)
            {
                $product_name = $product['title'];
                $product_id = $product['id'];

                foreach($product['variants'] as $var)
                {
                    $Variants = ([
                        'products['.$productsCount.'][name]' => $product_name.'('.$var['title'].')',
                        'products['.$productsCount.'][sku]' => $var['id'],
                        'products['.$productsCount.'][barCode]' =>($var['barcode'] != null) ? $var['barcode'] : (( $var['sku'] != null) ?  $var['sku'] : $var['inventory_item_id'] )
                    ]);
                    // $Variants = ([
                    //     'products['.$productsCount.'][name]' => $product_name.'('.$var['title'].')',
                    //     'products['.$productsCount.'][sku]' => $var['id'],
                    //     'products['.$productsCount.'][barCode]' =>$var['barcode']
                    // ]);

                    $productExist = DB::table('all_products')->where([['shopify_variant_id',$var['id']],['inventory_item_id',$var['inventory_item_id']]])->first();

                    if( $productExist ) {
                        if($productExist->product_name != $product_name.'('.$var['title'].')'  && $productExist->status == "sent" ){

                            array_push($productsArray, $Variants);
                            $productsCount++;
                            $productUpdate = DB::table('all_products')->where([['shopify_variant_id',$var['id']],['inventory_item_id',$var['inventory_item_id']]])->update([
                                'uid'                   => $userId,
                                'product_name'          => $product_name.'('.$var['title'].')',
                                'shopify_product_id'    => $product_id,
                                'barcode'               => $var['barcode'],
                                'sku'                   => $var['sku'],
                                'product_created_at'    => $product['created_at'],
                                'product_updated_at'    => $product['updated_at'],
                                'product_published_at'  => $product['published_at'],
                                'status'                => "sent"
                            ]);
                        }
                    } else {

                        array_push($productsArray, $Variants);
                        $productsCount++;
                        $productInsert = DB::table('all_products')->insert([
                            'uid'                   => $userId,
                            'product_name'          => $product_name.'('.$var['title'].')',
                            'shopify_product_id'    => $product_id,
                            'shopify_variant_id'    => $var['id'],
                            'inventory_item_id'     => $var['inventory_item_id'],
                            'barcode'               => $var['barcode'],
                            'sku'                   => $var['sku'],
                            'product_created_at'    => $product['created_at'],
                            'product_updated_at'    => $product['updated_at'],
                            'product_published_at'  => $product['published_at'],
                            'status'                => "sent"
                        ]);
                    }
                }
            }
            $collection = collect($productsArray);

            // $collapsed = $collection->collapse();

            // $collapsed->all();

            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Sent Object Start----------');
            // \Log::channel('sync_log')->info(json_decode( json_encode($collapsed), true));
            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Sent Object End----------');
            // // BULK API HIT
            // $curl = curl_init();
            // curl_setopt_array($curl, array(
            // CURLOPT_URL => 'https://api.neem.pro/shopify/product/bulk-create',
            // CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_ENCODING => '',
            // CURLOPT_MAXREDIRS => 10,
            // CURLOPT_TIMEOUT => 0,
            // CURLOPT_HEADER => 0,
            // CURLOPT_FOLLOWLOCATION => true,
            // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // CURLOPT_CUSTOMREQUEST => 'POST',
            // CURLOPT_POSTFIELDS => json_decode( json_encode($collapsed), true),
            // CURLOPT_HTTPHEADER => array(
            //     'api-token:'.$GernalSettingDetail->api_token,
            //     'api-user:' .$GernalSettingDetail->api_user
            //   ),
            // ));

            // $response = curl_exec($curl);
            // curl_close($curl);

            // $result = json_decode($response, true);
            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Response Start----------');
            // \Log::channel('sync_log')->info($result);
            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Response End----------');
            // \Log::channel('sync_log')->info($userId);
            // \Log::info(json_decode( json_encode($result), true));

            if(isset($productsShopify['link']))
            {
                if($productsShopify['link']['container']['next'] != null)
                {
                    $page_info = $productsShopify['link']['container']['next'];
                    $validatedData = ([
                    'next_token' => $page_info,
                    'uid' => $userId,
                    'product_count' => sizeof($productsShopify['body']['container']['products']),
                    'type' => $type,
                    'dateTime' => ''
                    ]);
                }
                else{
                    $validatedData = ([
                        'next_token' => "",
                        'uid' => $userId,
                        'product_count' => sizeof($productsShopify['body']['container']['products']),
                        'type' => $type,
                        'dateTime' => ''
                        ]);
                }
            }
            else
            {
                $validatedData = ([
                    'next_token' => "",
                    'uid' => $userId,
                    'product_count' => sizeof($productsShopify['body']['container']['products']),
                    'type' => $type,
                    'dateTime' => ''
                    ]);
            }

            sync_products::create($validatedData);
        }
        else
        {
            $last_page = true;
            $validatedData = ([
                'next_token' => "",
                'uid' => $userId,
                'product_count' => sizeof($productsShopify['body']['container']['products']),
                'type' => $type,
                'dateTime' => ''
                ]);
            sync_products::create($validatedData);
        }
        // if($userId==27){
        // $alreadySentProduct = DB::table('all_products')->where('uid',$userId)->where('status',null)->orWhere('status', 'sent')->get();
        // }else{

        // }
        $alreadySentProduct = DB::table('all_products')->where('status',null)->get();


        if($alreadySentProduct){
            // $alreadyproductsArray = array();

            // foreach ($alreadySentProduct as $key => $pro) {
            //     $alreadyVariants = ([
            //         'products['.$key.'][name]' => $pro->product_name,
            //         'products['.$key.'][sku]' => $pro->shopify_variant_id,
            //         'products['.$key.'][barCode]' => ($pro->barcode != null) ? $pro->barcode : (( $pro->sku != null) ?  $pro->sku : $pro->inventory_item_id )
            //     ]);
            //     array_push($alreadyproductsArray, $alreadyVariants);
            // }

            // $alreadycollection = collect($alreadyproductsArray);

            // $alreadycollapsed = $alreadycollection->collapse();

            // $alreadycollapsed->all();

            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Sent Object Start 2----------');
            // \Log::channel('sync_log')->info(json_decode( json_encode($alreadycollapsed), true));
            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Sent Object End 2----------');
            // // BULK API HIT
            // $curl = curl_init();
            // curl_setopt_array($curl, array(
            // CURLOPT_URL => 'https://api.neem.pro/shopify/product/bulk-create',
            // CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_ENCODING => '',
            // CURLOPT_MAXREDIRS => 10,
            // CURLOPT_TIMEOUT => 0,
            // CURLOPT_HEADER => 0,
            // CURLOPT_FOLLOWLOCATION => true,
            // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // CURLOPT_CUSTOMREQUEST => 'POST',
            // CURLOPT_POSTFIELDS => json_decode( json_encode($alreadycollapsed), true),
            // CURLOPT_HTTPHEADER => array(
            //     'api-token:'.$GernalSettingDetail->api_token,
            //     'api-user:' .$GernalSettingDetail->api_user
            //   ),
            // ));

            // $response = curl_exec($curl);
            // curl_close($curl);

            // $result = json_decode( $response, true);
            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Response Start 2----------');
            // \Log::channel('sync_log')->info($result);
            // \Log::channel('sync_log')->info('-----------Bulk Create Sync Product Response End 2----------');
            // \Log::channel('sync_log')->info($userId);

            // foreach ($alreadySentProduct as $key => $pro) {
            //     $alreadyProductUpdate = DB::table('all_products')->where([['shopify_variant_id',$pro->shopify_variant_id],['inventory_item_id',$pro->inventory_item_id]])->update([
            //         'status'  => "sent"
            //     ]);
            // }
        }
        // JobsExtension::where('job_id',$this->job->getJobId())->update(['status' => 'success']);
    }
}
