<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Like;

class BlogController extends Controller
{
	public function index(Request $request)
    {
        // Base query for blogs
        $query = Blog::query();

        // Apply filters
        if ($request->has('liked')) {
            $query->whereHas('likes', function ($likeQuery) {
                $likeQuery->where('user_id', auth()->id());
            });
        }

        if ($request->has('latest')) {
            $query->latest();
        }

        // Apply search
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($searchQuery) use ($searchTerm) {
                $searchQuery->where('title', 'like', "%$searchTerm%")
                            ->orWhere('description', 'like', "%$searchTerm%");
            });
        }

        // Paginate the results
        $blogs = $query->paginate(10);

        $blogs->getCollection()->transform(function ($blog) {
		   	$blog->is_liked = $blog->likes()->where('user_id', auth()->id())->exists();
		    return $blog;
		});


		$responseData = $blogs->toArray();

		$collection = collect($responseData['data']);

		$responseData['data'] = $collection->map(function ($item) {
		    return collect($item)->except(['likes'])->toArray();
		});


		$response = [
	        'success' => true,
	        'message' => 'Blog Listing.',
	        'data'    => $responseData['data'],
	    ];

	    return response()->json($response, 200);   
	}

    public function create(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust file types and size as needed
        ]);

        $user = auth()->user();

        $blog = new Blog();
        $blog->title = $request->input('title');
        $blog->description = $request->input('description');
        $imagePath = $request->file('image')->store('images', 'local');

        // Handle image upload and storage here
        $blog->image_path = $imagePath;
        $blog->user_id = $user->id;
        $blog->save();

		$response = [
	        'success' => true,
	        'message' => 'Blog created successfully.',
	        'data'    => $blog,
	    ];

	    return response()->json($response, 200);
    }


    public function toggleLike(Request $request, Blog $blog)
    {
	    try {
	    	$user = auth()->user();

	        // Check if the user has already liked the blog
	        $isLiked = $blog->likes()->where('user_id', $user->id)->exists();

	        if ($isLiked) {
	            $blog->likes()->where('user_id', $user->id)->delete();
	        } else {
	            // User has not liked the blog, so add the like
	            $like = new Like();
	            $like->user_id = $user->id;
	            $like->blog_id = $blog->id;
	            $like->likeable_id = $blog->id;
	            $like->save();
	        }

			$response = [
		        'success' => true,
		        'message' => 'Like toggled successfully.',
		        'data'    => (object)[],
		    ];


		    return response()->json($response, 200);
		} 
		catch (\Illuminate\Database\QueryException $e) {
		    if ($e->errorInfo[1] === 1062) {
		        return response()->json(['error' => 'You have already liked this blog post.'], 422);
		    } else {
		        return response()->json(['error' => 'Database error.'], 500);
		    }
		}
    }
}
