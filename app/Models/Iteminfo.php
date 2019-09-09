<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iteminfo extends Model
{
    public $timestamps = false;
    protected $table = 'iteminfo';
    protected $primaryKey = 'ItemNO';
    protected $fillable = [
        'Question',
        'Option1',
        'Option2',
        'Option3',
        'Option4',
        'Option5',
        'Option6',
        'Answer',
        'Hint1',
        'Hint2',
        'Explain1',
        'Explain2',
        'Author',
        'Date',
        'URL',
        'Edu',
        'Subject',
        'Volume',
        'Textbook',
        'Unit',
        'Session1',
        'Session2',
        'Session3',
        'Session4',
        'Type',
        'UpLink',
        'DownLink',
        'Knowledge',
        'Analysis',
        'Understanding',
        'Application',
        'Integration',
        'Evaluation',
        'Concept1',
        'Concept2',
        'Concept3',
        'Difficulty',
        'Guessability',
        'Distinguishability',
        'RWType',
        'Status',
        'ActionMode',
        'QRTFFile',
        'HRTFFile',
        'ERTFFile',
        'GUID',
        'Format',
        'BGImage',
        'BGAudio',
        'ExtraMaterialExplain',
    ];

    public function Testitem()
    {
        return $this->hasMany(Testitem::class,'ItemNO');
    }
}
