<div class="e-signpad" data-disabled-without-signature="{{ $disabledWithoutSignature }}" style="display: flex; flex-direction: column; align-items: center">
    <canvas id="signature-canvas" style="touch-action: none; border: 2px solid {{ $borderColor }}; max-width: 100%; cursor: crosshair; background: white;" width="{{ $width }}" height="{{ $height }}" class="{{ $padClasses }}"></canvas>
    <div style="margin-top: 15px;">
        <input type="hidden" name="sign" class="sign">
        <button type="button" class="sign-pad-button-clear {{$buttonClasses}}" style="margin-right: 10px;">{!! $clearName !!}</button>
        <button type="submit" class="sign-pad-button-submit {{$buttonClasses}}" {{ $disabledWithoutSignature ? 'disabled' : '' }}>{!! $submitName !!}</button>
    </div>
    <div id="debug-info" style="margin-top: 10px; font-size: 12px; color: #666; text-align: center;"></div>
</div>

<script src="{{ asset('vendor/sign-pad/sign-pad.min.js') }}"></script>
<script>
    console.log('Script loading...');

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing signature pad...');

        const canvas = document.getElementById('signature-canvas');
        const signatureInput = document.querySelector('.e-signpad .sign');
        const clearButton = document.querySelector('.e-signpad .sign-pad-button-clear');
        const submitButton = document.querySelector('.e-signpad .sign-pad-button-submit');
        const debugInfo = document.getElementById('debug-info');

        console.log('Elements found:', {
            canvas: !!canvas
            , signatureInput: !!signatureInput
            , clearButton: !!clearButton
            , submitButton: !!submitButton
            , SignaturePad: typeof SignaturePad
        });

        if (debugInfo) {
            debugInfo.innerHTML = 'Canvas: ' + (canvas ? 'Found' : 'Not found') +
                ' | SignaturePad: ' + (typeof SignaturePad) +
                ' | Canvas size: ' + (canvas ? canvas.width + 'x' + canvas.height : 'N/A');
        }

        if (canvas) {
            // Test basic mouse events first
            canvas.addEventListener('mousedown', function(e) {
                console.log('Mouse down at:', e.offsetX, e.offsetY);
                if (debugInfo) {
                    debugInfo.innerHTML = 'Mouse down at: ' + e.offsetX + ',' + e.offsetY;
                }
            });

            canvas.addEventListener('mousemove', function(e) {
                if (e.buttons === 1) { // Left mouse button pressed
                    console.log('Mouse drag at:', e.offsetX, e.offsetY);
                    if (debugInfo) {
                        debugInfo.innerHTML = 'Dragging at: ' + e.offsetX + ',' + e.offsetY;
                    }
                }
            });

            canvas.addEventListener('mouseup', function(e) {
                console.log('Mouse up');
                if (debugInfo) {
                    debugInfo.innerHTML = 'Mouse up';
                }
            });

            // Initialize SignaturePad if available
            if (typeof SignaturePad !== 'undefined') {
                try {
                    const signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255, 255, 255, 1)'
                        , penColor: 'rgb(0, 0, 0)'
                        , minWidth: 1
                        , maxWidth: 3
                    , });

                    console.log('SignaturePad initialized successfully');

                    if (debugInfo) {
                        debugInfo.innerHTML += ' | SignaturePad: Initialized';
                    }

                    // Clear button functionality
                    if (clearButton) {
                        clearButton.addEventListener('click', function() {
                            console.log('Clear button clicked');
                            signaturePad.clear();
                            if (signatureInput) signatureInput.value = '';
                            if (submitButton && {
                                    {
                                        $disabledWithoutSignature ? 'true' : 'false'
                                    }
                                }) {
                                submitButton.disabled = true;
                            }
                            if (debugInfo) {
                                debugInfo.innerHTML = 'Canvas cleared';
                            }
                        });
                    }

                    // Update hidden input when signature changes
                    signaturePad.addEventListener('endStroke', function() {
                        console.log('Stroke ended');
                        if (signatureInput) {
                            signatureInput.value = signaturePad.toDataURL();
                            console.log('Signature data length:', signatureInput.value.length);
                        }
                        if (submitButton && {
                                {
                                    $disabledWithoutSignature ? 'true' : 'false'
                                }
                            }) {
                            submitButton.disabled = signaturePad.isEmpty();
                            console.log('Submit button disabled:', submitButton.disabled);
                        }
                        if (debugInfo) {
                            debugInfo.innerHTML = 'Signature captured - strokes: ' + signaturePad.toData().length + ' | Data length: ' + (signatureInput ? signatureInput.value.length : 0);
                        }
                    });

                    // Add form submission debugging
                    const form = document.querySelector('.e-signpad').closest('form');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            console.log('Form submitting...');
                            console.log('Signature data:', signatureInput ? signatureInput.value.substring(0, 100) + '...' : 'No signature data');
                            console.log('Form action:', form.action);
                            console.log('Form method:', form.method);

                            if (!signatureInput || !signatureInput.value) {
                                console.error('No signature data found!');
                                e.preventDefault();
                                alert('Please draw your signature before submitting.');
                                return false;
                            }
                        });
                    }

                    // Test drawing programmatically
                    setTimeout(function() {
                        console.log('Testing programmatic drawing...');
                        signaturePad.fromData([{
                            color: 'rgb(0, 0, 0)'
                            , points: [{
                                    x: 50
                                    , y: 50
                                    , pressure: 0.5
                                    , time: Date.now()
                                }
                                , {
                                    x: 100
                                    , y: 100
                                    , pressure: 0.5
                                    , time: Date.now() + 100
                                }
                            ]
                        }]);
                        setTimeout(() => signaturePad.clear(), 2000);
                    }, 1000);

                } catch (error) {
                    console.error('Error initializing SignaturePad:', error);
                    if (debugInfo) {
                        debugInfo.innerHTML = 'Error: ' + error.message;
                    }
                }
            } else {
                console.error('SignaturePad not available');
                if (debugInfo) {
                    debugInfo.innerHTML = 'Error: SignaturePad library not loaded';
                }
            }
        } else {
            console.error('Canvas not found');
        }
    });

</script>
