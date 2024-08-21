<?php

namespace App\Http\Controllers;

use App\Models\Variation;
use App\Models\VariationTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VariationController extends Controller
{
    public function variation(){
        $variationTitles = VariationTitle::all(); // this course come from modal
        return view('variation.variation',compact('variationTitles'));
    }

    public function index()
    {
        $getValue = Variation::all();
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

        $validator = Validator::make($request->all(),
        [
            'variation_title_id' => 'required',
            'variation_value' => 'required',
        ],

        //when you add custom validate
         [
            'variation_title_id.*required' => 'Please select the variation.',
            'variation_value.*required' => 'Please select the variable value.',
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make(
            $request->all(),
            [

                'variation_value' => 'required',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = Variation::find($id);

            if ($getValue) {
                $getValue->update([

                  'variation_value' => $request->variation_value,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Variation  Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Variation Found!"
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
        $getValue = Variation::find($id);
        if ($getValue) {

            $getValue->delete();
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
