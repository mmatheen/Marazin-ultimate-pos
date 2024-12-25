<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\OpeningStock;
use App\Models\Batch;
use Illuminate\Http\Request;
use App\Exports\ExportOpeningStock;
use App\Imports\ImportOpeningStock;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\ExportOpeningStockTemplate;

class OpeningStockController extends Controller
{
    public function importOpeningStock(){
        $locations = Location::all();
        $products = Product::all();
        return view('stock.import_opening_stock',compact('locations','products'));
    }

    public function index()
    {

        $getValue = OpeningStock::with(['location', 'product'])->get();
        if ($getValue->count() > 0) {

            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // dd($request->all());
        $validator = Validator::make(
            $request->all(),
            [
                'sku' => [
                    'nullable',
                    'string',
                    'max:255',
                    'unique:opening_stocks',
                    function($attribute, $value, $fail) {
                        // Custom rule for location_id format
                        if (!preg_match('/^SKU\d{4}$/', $value)) {
                            $fail('The ' . $attribute . ' must be in the format SKU followed by 4 digits. eg:  SKU0001');
                        }
                    }
                ],
                'location_id' => 'required|string|max:255',
                'product_id' => 'required|string|max:255',
                'quantity' => 'required|string|max:255',
                'unit_cost' => 'required|string|max:255',
                'lot_no' => 'required|string|max:255',
                'expiry_date' => 'required|string|max:255',
            ],
            [
                'sku.unique' => 'The location_id has already been taken.',
            ]
        );


      // Custom logic for generating location_id auto-increment code start

      // Generate location_id only if not provided
      $sku = $request->sku;
      if (!$sku) {
        // Custom logic for generating location_id auto-increment code
        $prefix = 'SKU'; // The prefix for location_id
        $latestSKU = OpeningStock::where('sku', 'like', $prefix . '%')->orderBy('sku', 'desc')->first();

        // Extract the numeric part of the latest location_id and increment it
        if ($latestSKU) {
            // Extract numeric part after the prefix 'LOC'
            $latestID = intval(substr($latestSKU->sku, strlen($prefix)));
        } else {
            $latestID = 1; // If no record found, start from 1
        }

        $nextID = $latestID + 1;
        $sku = $prefix . sprintf("%04d", $nextID); // Format as LOC0001, LOC0002, etc.

        // Check for uniqueness of the generated location_id and regenerate if necessary
        while (OpeningStock::where('sku', $sku)->exists()) {
            $nextID++;
            $sku = $prefix . sprintf("%04d", $nextID);
        }
    }
        // Custom logic for generating location_id auto-increment code end



        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = OpeningStock::create([


                'sku' => $sku, // Use unique generated sku ID
                'location_id' => $request->location_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_cost' => $request->unit_cost,
                'lot_no' => $request->lot_no,
                'expiry_date' => $request->expiry_date,

            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Opening Stock Details Created Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "Something went wrong!"
                ]);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $getValue = OpeningStock::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Opening Stock Found!"
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id)
    {
        $getValue = OpeningStock::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Opening Stock Found!"
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
       // dd($request->all());
       $validator = Validator::make(
        $request->all(),
        [
            'sku' => [
                'nullable',
                'string',
                'max:255',
                'unique:opening_stocks',
                function($attribute, $value, $fail) {
                    // Custom rule for location_id format
                    if (!preg_match('/^SKU\d{4}$/', $value)) {
                        $fail('The ' . $attribute . ' must be in the format SKU followed by 4 digits. eg:  SKU0001');
                    }
                }
            ],
            'location_id' => 'required|string|max:255',
            'product_id' => 'required|string|max:255',
            'quantity' => 'required|string|max:255',
            'unit_cost' => 'required|string|max:255',
            'lot_no' => 'required|string|max:255',
            'expiry_date' => 'required|string|max:255',
        ],
        [
            'sku.unique' => 'The sku has already been taken.',
        ]
    );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = OpeningStock::find($id);

            if ($getValue) {
                $getValue->update([

                'sku' => $request->sku, // Use unique generated sku ID
                'location_id' => $request->location_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_cost' => $request->unit_cost,
                'lot_no' => $request->lot_no,
                'expiry_date' => $request->expiry_date,

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Opening Stock  Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such OpeningStock Found!"
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $getValue = OpeningStock::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Opening Stock Details Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Opening Stock Found!"
            ]);
        }
    }


    public function exportBlankTemplate()
    {
        return Excel::download(new ExportOpeningStockTemplate, 'Import Opening Stock Blank Template.xlsx');
    }
    public function export()
    {
        return Excel::download(new ExportOpeningStock, 'Opening Stock Details.xlsx');
    }



    public function importOpeningStockStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }
        // Check if file is present in the request
        if ($request->hasFile('file')) {
            // Get the uploaded file
            $file = $request->file('file');

            // Check if file upload was successful
            if ($file->isValid()) {
                // Process the Excel file

                Excel::import(new ImportOpeningStock, $file);
                return response()->json([
                    'status' => 200,
                    'message' => "Import Opening Stock Excel file Uploated successfully!"
                ]);
            }
        }
    }
}
