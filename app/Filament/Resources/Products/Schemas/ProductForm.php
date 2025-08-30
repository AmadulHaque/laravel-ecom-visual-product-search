<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('price')
                ->required()
                ->numeric()
                ->prefix('$'),

            Forms\Components\FileUpload::make('image_path')
                ->image()
                ->required()
                ->directory('products')
                ->visibility('public'),
        ]);
    }
}
