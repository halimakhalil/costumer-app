<?php

    namespace App\Imports;

    use App\Models\error;
    use Illuminate\Database\Eloquent\Model;
    use Log;
    use Maatwebsite\Excel\Concerns\ToModel;
    use Maatwebsite\Excel\Concerns\WithHeadingRow;
    use Maatwebsite\Excel\Concerns\Importable;
    use Carbon\Carbon;
    use DB;

    class BulkImport implements ToModel, WithHeadingRow
    {
        use Importable;

        /**
         * @param array $row
         *
         * @return Model|null
         */
        public function model(array $row)
        {

            $url = 'https://ebbbaca475eff149feeb954a8d6a698d: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@testmean3.myshopify.com/admin/api/2023-01/customers/search.json?query=email:'.$row['user_email'];
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            if(empty($response['customers'])){
                $data = array(
                    "customer" => array(
                        "first_name" => $row[ 'first_name' ],
                        "last_name" => $row[ 'last_name' ],
                        "email" => $row[ 'user_email' ],
                        "accepts_marketing" => true,
                        "addresses" => array(
                            array(
                                "address1" => $row[ 'shipping_address_1' ],
                                "city" => $row[ 'shipping_city' ],
                                "country" => $row[ 'shipping_country' ],
                                "first_name" => $row[ 'first_name' ],
                                "last_name" => $row[ 'last_name' ],
                                "province" => $row[ 'shipping_state' ],
                                "phone" => $row[ 'phone' ],
                            ),
                        ),
                    ),
                );
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://ebbbaca475eff149feeb954a8d6a698d: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@testmean3.myshopify.com/admin/api/2023-01/customers.json',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                    ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);

            } elseif (!empty($response['customers'])){
                \Log::info($data);
            }
        }
    }
