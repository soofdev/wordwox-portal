// Custom CKEditor 5 Build with Alignment, Image Upload, and Language Support
import { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Link, List, BlockQuote, Table, TableToolbar, Undo, Alignment } from 'ckeditor5';
import { Image, ImageToolbar, ImageCaption, ImageStyle, ImageUpload } from '@ckeditor/ckeditor5-image';
import { SimpleUploadAdapter } from '@ckeditor/ckeditor5-upload';

// Export the custom editor
export default class CustomClassicEditor extends ClassicEditor {
    static builtinPlugins = [
        Essentials,
        Paragraph,
        Heading,
        Bold,
        Italic,
        Link,
        List,
        BlockQuote,
        Table,
        TableToolbar,
        Undo,
        Alignment,
        Image,
        ImageToolbar,
        ImageCaption,
        ImageStyle,
        ImageUpload,
        SimpleUploadAdapter
    ];

    static defaultConfig = {
        toolbar: {
            items: [
                'heading',
                '|',
                'bold',
                'italic',
                'link',
                '|',
                'alignment',
                '|',
                'bulletedList',
                'numberedList',
                '|',
                'blockQuote',
                'insertTable',
                'imageUpload',
                '|',
                'undo',
                'redo'
            ],
            shouldNotGroupWhenFull: true
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
            ]
        },
        alignment: {
            options: ['left', 'center', 'right', 'justify']
        },
        image: {
            toolbar: [
                'imageStyle:inline',
                'imageStyle:block',
                'imageStyle:side',
                '|',
                'imageTextAlternative',
                'toggleImageCaption',
                '|',
                'linkImage'
            ]
        }
    };
}

