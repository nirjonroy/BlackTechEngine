<?php

namespace App\Enums;

enum AiModelModality: string
{
    case Text = 'text';
    case Image = 'image';
    case Embedding = 'embedding';
    case Multimodal = 'multimodal';
}
