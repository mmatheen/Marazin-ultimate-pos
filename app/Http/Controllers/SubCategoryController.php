<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubCategory;
use App\Models\MainCategory;
use Illuminate\Support\Facades\Validator;

class SubCategoryController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view sub-catagory', ['only' => ['index', 'show','SubCategory']]);
        $this->middleware('permission:create sub-category', ['only' => ['store']]);
        $this->middleware('permission:edit sub-category', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete sub-category', ['only' => ['destroy']]);
    }

    public function SubCategory(){

        $MainCategories = MainCategory::all();
        return view('category.sub_category.sub_category', compact('MainCategories'));

    }


    public function index()
    {
        $getValue = SubCategory::with('mainCategory')->get();
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
                'subCategoryname' => 'required|string',
                'main_category_id' => 'required|integer',

            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = SubCategory::create([
                'subCategoryname' => $request->subCategoryname,
                'main_category_id' => $request->main_category_id,
                'subCategoryCode' => $request->subCategoryCode,
                'description' => $request->description ?? '',

            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Sub Category Details Created Successfully!"
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
        $getValue = SubCategory::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Sub Category Found!"
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
        $getValue = SubCategory::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Sub Category Found!"
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
                'subCategoryname' => 'required|string',
                'main_category_id' => 'required|integer',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = SubCategory::find($id);

            if ($getValue) {
                $getValue->update([

                    'subCategoryname' => $request->subCategoryname,
                    'main_category_id' => $request->main_category_id,
                    'subCategoryCode' => $request->subCategoryCode,
                    'description' => $request->description ?? '',

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Sub Category Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Sub Category Found!"
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
        $getValue = SubCategory::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Sub Category Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Sub Category Found!"
            ]);
        }
    }
}
