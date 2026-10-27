<?php

namespace App\Enums;

enum AiModelCapability: string
{
    case TextGeneration = 'text_generation';
    case StructuredOutput = 'structured_output';
    case ImageGeneration = 'image_generation';
    case Embeddings = 'embeddings';
}
