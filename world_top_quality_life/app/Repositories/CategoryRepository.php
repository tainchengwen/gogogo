<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class CategoryRepository extends BaseRepository
{
    // 深度 1 2 3三层
    // 获取分类列表
    public function getCategoryTreeByDeep($deep = 1)
    {
        // 生成树，并且桉树渲染
        $deep = $deep -1;

        $categories = DB::table('erp_spu_category')
        -> where('path','')
        -> where('mp_flag', 1)
        -> select([
            'id',
            'name',
            'code',
            'path',
            'image'
        ])
        -> orderBy('sort_index','desc')
        -> orderBy('id','desc')
        -> get();

        return $this->generateTreeByDeep($categories, $deep);
    }

    public function getCategoryIdsAndChild($categorId)
    {
        $categories = DB::table('erp_spu_category')
        -> where('path','like','%,'.$categorId)
        -> where('mp_flag', 1)
        -> select([
            'id',
            'name',
            'code',
            'path',
            'image'
        ])
        -> orderBy('sort_index','desc')
        -> orderBy('id','desc')
        -> get();
        $ids = [$categorId];
        foreach ($categories as $key => $category) {
            $ids[] = $category->id;
        }
        return $ids;
    }

    private function generateTreeByDeep($categories, $deep)
    {
        foreach ($categories as $key => $category) {
            $currentDeep = count(explode(",", $category->path));
            $categories[$key]->deep = $currentDeep;
            $categories[$key]->imageUrl = getImageUrl($category -> image);
            unset($categories[$key]->image);

            if ($currentDeep <= $deep) {
                $children = DB::table('erp_spu_category')
                -> where('path','like','%,'.$category->id)
                -> where('mp_flag', 1)
                -> select([
                    'id',
                    'name',
                    'code',
                    'path',
                    'image'
                ])
                -> orderBy('sort_index','desc')
                -> orderBy('id','desc')
                -> get();
                // $categories[$key]->haveChildren = count($children) > 0;
                if (count($children)) {
                    $categories[$key]->children = $children;
                }
                if(count($children) && $currentDeep <= $deep){
                    $this->generateTreeByDeep($children, $deep);
                }
            }
        }

        return $categories;
    }

}