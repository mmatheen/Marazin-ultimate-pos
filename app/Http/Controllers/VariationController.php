<?php

namespace App\Http\Controllers;

use App\Models\Variation;
use App\Models\VariationTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VariationController extends Controller
{
    public function variation()
    {
        $variationTitles = VariationTitle::all(); // this course come from modal
        return view('variation.variation.variation', compact('variationTitles'));
    }

    public function index()
    {
        $getValue = Variation::with('variationTitle')->get();
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
        $validator = Validator::make($request->all(), [
            'variation_title_id' => 'required|array',
            'variation_value' => 'required|array',
        ], [
            'variation_title_id.required' => 'Please select the variation.',
            'variation_value.required' => 'Please select the variable value.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $variation_title_ids = $request->variation_title_id;
        $variation_values = $request->variation_value;

        $variations = [];

        foreach ($variation_title_ids as $variation_title_id) {
            foreach ($variation_values as $variation_value) {
                $variations[] = [
                    'variation_title_id' => $variation_title_id,
                    'variation_value' => $variation_value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $getValue = Variation::insert($variations);

        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => "New Variation Details Created Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong!"
            ]);
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
        $getValue = Variation::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Variation Found!"
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
        // Find all records with the matching variation_title_id
        // $getValue = Variation::where('variation_title_id', $id)->get();
        $getValue = Variation::with('variationTitle')->where('variation_title_id', $id)->get();
        // $getValue = Variation::find($id);

        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Variation Found!"
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
        $validator = Validator::make($request->all(), [
            'variation_title_id' => 'required|array',
            'variation_value' => 'required|array',
        ], [
            'variation_title_id.required' => 'Please select the variation title.',
            'variation_value.required' => 'Please select variation_value.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $variation_title_ids = $request->variation_title_id;
        $variation_values = $request->variation_value;

        foreach ($variation_title_ids as $variation_title_id) {
            foreach ($variation_values as $variation_value) {
                // Update the variation_title_id and variation_value
                Variation::where('variation_title_id', $id)->update([
                        'variation_value' => $variation_value,
                        'variation_title_id' => $variation_title_id,
                        'updated_at' => now(),
                    ]);
            }
        }

        return response()->json([
            'status' => 200,
            'message' => "Old Variation  Details Updated Successfully!"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        // Find all records with the matching variation_title_id
        $records = Variation::where('variation_title_id', $id)->get();

        if ($records->isNotEmpty()) {
            // Loop through and delete each record
            foreach ($records as $record) {
                $record->delete();
            }

            return response()->json([
                'status' => 200,
                'message' => "Variation Details Deleted Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Variation Found!"
            ]);
        }
    }
}
