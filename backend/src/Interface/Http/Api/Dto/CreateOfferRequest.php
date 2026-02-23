<?php

namespace App\Interface\Http\Api\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateOfferRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $title = '';
}
