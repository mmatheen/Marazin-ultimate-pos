<?php

namespace App\Http\Controllers;

use App\Models\VariationTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VariationTitleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view variation-title', ['only' => ['index', 'show', 'variationTitle']]);
        $this->middleware('permission:create variation-title', ['only' => ['store']]);
        $this->middleware('permission:edit variation-title', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete variation-title', ['only' => ['destroy']]);
    }

    public function variationTitle(){

        return  view('variation.variation_title.variation_title');
    }
    public function index()
    {

         $getValue = VariationTitle::all();
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
                'variation_title' => 'required|string|unique:variation_titles',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = VariationTitle::create([
                'variation_title' => $request->variation_title,
            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Variation Title Created Successfully!"
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
        $getValue = VariationTitle::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Variation Title Found!"
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
        $getValue = VariationTitle::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Variation Title Found!"
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
                'variation_title' => 'required|string',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = VariationTitle::find($id);

            if ($getValue) {
                $getValue->update([

                'variation_title' => $request->variation_title,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Variation Title Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Variation Title Found!"
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
        $getValue = VariationTitle::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Variation Title Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Variation Title Found!"
            ]);
        }
    }
}
