<?php

namespace App\Features\Media\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Media\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $collection = $request->get('collection', 'default');
        $media = Media::where('collection_name', $collection)
            ->latest()
            ->paginate(24);

        return response()->json([
            'status' => 'success',
            'data' => $media
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120', // 5MB max
            'collection' => 'nullable|string'
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('media', $fileName, 'public');

        $media = Media::create([
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'collection_name' => $request->get('collection', 'default'),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $media
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);
        $request->validate([
            'original_name' => 'required|string|max:255'
        ]);

        $media->update([
            'original_name' => $request->original_name
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $media
        ]);
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Media deleted'
        ]);
    }
}
