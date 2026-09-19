<?php

namespace App\Http\Controllers;

use App\Models\Poster;

class LandingController extends Controller
{
    /**
     * Show the public landing page.
     */
    public function index()
    {
        $heroImage = 'images/hero/SSCfrontview.jpg';

        $posters = Poster::active()->ordered()->get()->map(function (Poster $poster) {
            return [
                'image'       => $poster->image_path ? 'storage/' . $poster->image_path : null,
                'title'       => $poster->title,
                'date'        => optional($poster->event_date)->format('M j, Y'),
                'time'        => $poster->event_time,
                'unit'        => $poster->unit,
                'description' => $poster->description,
                'status'      => ucfirst($poster->status), // open|full|ongoing -> Open|Full|Ongoing
            ];
        })->toArray();

        $facilities = [
            [
                'name'  => 'Function 1',
                'meta'  => '2nd Floor, Front · 300 pax',
                'image' => 'images/carousel/function-1.jpg',
            ],
            [
                'name'  => 'Function 2',
                'meta'  => '2nd Floor, Rear · 200 pax',
                'image' => 'images/carousel/function-2.jpg',
            ],
            [
                'name'  => 'Function 3',
                'meta'  => '1st Floor, near the Whole Court · 200 pax',
                'image' => 'images/carousel/function-3.jpg',
            ],
            [
                'name'  => 'Lobby & Whole Court',
                'meta'  => 'Ground Floor · 2,000 pax',
                'image' => 'images/carousel/whole-court.jpg',
            ],
            [
                'name'  => 'Community Events',
                'meta'  => 'Leagues, municipal events & private functions',
                'image' => 'images/carousel/community.jpg',
            ],
        ];

        $location = [
            'label'       => 'Municipal Gymnasium, Subic',
            'address'     => 'Wawandue, Subic, Zambales',
            'mapEmbedUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1235.2587239742536!2d120.23062115382251!3d14.878052964674827!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x339677c8e5913fc3%3A0xeef7305da411165f!2sMunicipal%20Gymnasium%20Subic!5e1!3m2!1sen!2sph!4v1789180792225!5m2!1sen!2sph',
        ];

        return view('landing', compact('heroImage', 'facilities', 'location', 'posters'));
    }
}