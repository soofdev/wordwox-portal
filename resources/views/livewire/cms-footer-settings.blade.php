<div class="min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Footer Settings</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage your website footer content and information</p>
        </div>

        <form wire:submit.prevent="save" class="space-y-8">
            <!-- Inspirational Quote Section -->
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Inspirational Quote</h2>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="quoteIsActive"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                            />
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $quoteIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </label>
                    </div>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Quote Text</flux:label>
                        <flux:textarea 
                            wire:model="quoteText" 
                            rows="3"
                            placeholder="The body benefits from movement, and the mind benefits from stillness."
                        />
                    </flux:field>
                    <flux:field>
                        <flux:label>Quote Author</flux:label>
                        <flux:input 
                            wire:model="quoteAuthor" 
                            placeholder="Ancient Wisdom"
                        />
                    </flux:field>
                </div>
            </flux:card>

            <!-- About Section -->
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">About Section</h2>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="aboutIsActive"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                            />
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $aboutIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </label>
                    </div>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input 
                            wire:model="aboutTitle" 
                            placeholder="Wodworx"
                        />
                    </flux:field>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea 
                            wire:model="aboutDescription" 
                            rows="4"
                            placeholder="Discover the perfect balance of strength and serenity..."
                        />
                    </flux:field>
                    <flux:field>
                        <flux:label>Social Media Links</flux:label>
                        <div class="space-y-3">
                            @foreach($socialLinks as $index => $social)
                                <div class="flex gap-3">
                                    <flux:input 
                                        wire:model="socialLinks.{{ $index }}.icon" 
                                        placeholder="🧘‍♀️"
                                        class="w-24"
                                    />
                                    <flux:input 
                                        wire:model="socialLinks.{{ $index }}.url" 
                                        placeholder="https://..."
                                        class="flex-1"
                                    />
                                    <flux:button 
                                        wire:click="removeSocialLink({{ $index }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        class="text-red-600"
                                    />
                                </div>
                            @endforeach
                            <flux:button 
                                wire:click="addSocialLink"
                                variant="outline"
                                size="sm"
                                icon="plus"
                            >
                                Add Social Link
                            </flux:button>
                        </div>
                    </flux:field>
                </div>
            </flux:card>

            <!-- Classes Section -->
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Classes Section</h2>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="classesIsActive"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                            />
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $classesIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </label>
                    </div>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input 
                            wire:model="classesTitle" 
                            placeholder="🌿 Mindful Classes"
                        />
                    </flux:field>
                    <flux:field>
                        <flux:label>Class Items</flux:label>
                        <div class="space-y-3">
                            @foreach($classesItems as $index => $item)
                                <div class="flex gap-3">
                                    <flux:input 
                                        wire:model="classesItems.{{ $index }}" 
                                        placeholder="Yoga Flow"
                                        class="flex-1"
                                    />
                                    <flux:button 
                                        wire:click="removeClassItem({{ $index }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        class="text-red-600"
                                    />
                                </div>
                            @endforeach
                            <flux:button 
                                wire:click="addClassItem"
                                variant="outline"
                                size="sm"
                                icon="plus"
                            >
                                Add Class Item
                            </flux:button>
                        </div>
                    </flux:field>
                </div>
            </flux:card>

            <!-- Contact Section -->
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Contact Section</h2>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="contactIsActive"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                            />
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $contactIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </label>
                    </div>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input 
                            wire:model="contactTitle" 
                            placeholder="🏛️ Sacred Space"
                        />
                    </flux:field>
                    <flux:field>
                        <flux:label>Address</flux:label>
                        <flux:input 
                            wire:model="contactAddress" 
                            placeholder="123 Serenity Lane"
                        />
                    </flux:field>
                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <flux:input 
                            wire:model="contactPhone" 
                            type="tel"
                            placeholder="+1 (555) 123-PEACE"
                        />
                    </flux:field>
                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input 
                            wire:model="contactEmail" 
                            type="email"
                            placeholder="hello@superhero.wodworx.com"
                        />
                    </flux:field>
                </div>
            </flux:card>

            <!-- Hours Section -->
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Hours Section</h2>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="hoursIsActive"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                            />
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $hoursIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </label>
                    </div>
                </div>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Section Title</flux:label>
                        <flux:input 
                            wire:model="hoursTitle" 
                            placeholder="Sacred Hours"
                        />
                    </flux:field>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <h3 class="font-medium text-zinc-900 dark:text-white">Weekdays</h3>
                            <flux:field>
                                <flux:label>Days</flux:label>
                                <flux:input 
                                    wire:model="weekdaysDays" 
                                    placeholder="Monday - Friday"
                                />
                            </flux:field>
                            <flux:field>
                                <flux:label>Time</flux:label>
                                <flux:input 
                                    wire:model="weekdaysTime" 
                                    placeholder="6:00 AM - 9:00 PM"
                                />
                            </flux:field>
                            <flux:field>
                                <flux:label>Note</flux:label>
                                <flux:input 
                                    wire:model="weekdaysNote" 
                                    placeholder="Morning meditation at sunrise"
                                />
                            </flux:field>
                        </div>
                        <div class="space-y-3 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <h3 class="font-medium text-zinc-900 dark:text-white">Weekend</h3>
                            <flux:field>
                                <flux:label>Days</flux:label>
                                <flux:input 
                                    wire:model="weekendDays" 
                                    placeholder="Saturday - Sunday"
                                />
                            </flux:field>
                            <flux:field>
                                <flux:label>Time</flux:label>
                                <flux:input 
                                    wire:model="weekendTime" 
                                    placeholder="7:00 AM - 7:00 PM"
                                />
                            </flux:field>
                            <flux:field>
                                <flux:label>Note</flux:label>
                                <flux:input 
                                    wire:model="weekendNote" 
                                    placeholder="Extended weekend sessions"
                                />
                            </flux:field>
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- Copyright Section -->
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Copyright</h2>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="copyrightIsActive"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                            />
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $copyrightIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </label>
                    </div>
                </div>
                <flux:field>
                    <flux:label>Copyright Text</flux:label>
                    <flux:input 
                        wire:model="copyrightText" 
                        placeholder="Nurturing transformation with love."
                    />
                    <flux:description>The year will be automatically added</flux:description>
                </flux:field>
            </flux:card>

            <!-- Save Button -->
            <div class="flex justify-end">
                <flux:button 
                    type="submit"
                    variant="primary"
                    icon="check"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Save Footer Settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>


        </form>
    </div>
    
    <!-- Flux Toast Component -->
    <flux:toast position="top end" />
</div>

