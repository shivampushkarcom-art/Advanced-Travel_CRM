/**
 * ATC Stories JavaScript
 * Instagram/Facebook Stories-like functionality
 * Mobile-first with touch gestures
 */

(function($) {
    'use strict';
    
    var StoryViewer = {
        stories: [],
        currentStoryIndex: 0,
        currentMediaIndex: 0,
        viewer: null,
        progressBars: [],
        timer: null,
        isPaused: false,
        touchStartX: 0,
        touchEndX: 0,
        touchStartY: 0,
        touchEndY: 0,
        cachedGlobalStories: null,
        soundEnabled: true,
        
        init: function() {
            this.createViewer();
            this.bindEvents();
        },
        
        createViewer: function() {
            var viewerHTML = `
                <div class="atc-story-viewer" id="atc-story-viewer">
                    <div class="atc-story-progress-container" id="atc-story-progress-container"></div>
                    <div class="atc-story-media-container" id="atc-story-media-container"></div>
                    <div class="atc-story-content" id="atc-story-content"></div>
                    <button class="atc-story-nav atc-story-nav-prev" id="atc-story-nav-prev" aria-label="Previous story">‹</button>
                    <button class="atc-story-nav atc-story-nav-next" id="atc-story-nav-next" aria-label="Next story">›</button>
                    <button class="atc-story-sound-toggle is-sound-on" id="atc-story-sound-toggle" aria-label="Mute audio" type="button">
                        <span class="atc-story-sound-icon" aria-hidden="true"></span>
                    </button>
                    <button class="atc-story-nav-close" id="atc-story-nav-close" aria-label="Close story viewer">×</button>
                </div>
            `;
            
            $('body').append(viewerHTML);
            this.viewer = $('#atc-story-viewer');
            this.updateSoundToggle();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Open story on click
            $(document).on('click', '.atc-story-item', function(e) {
                e.preventDefault();
                var storyIndex = parseInt($(this).attr('data-story-index'), 10);
                if (isNaN(storyIndex) || storyIndex < 0) {
                    storyIndex = 0;
                }
                var storiesData = self.getStoriesFromItem($(this));
                self.openStory(storyIndex, storiesData);
            });
            
            // Close viewer
            $(document).on('click', '#atc-story-nav-close', function() {
                self.closeViewer();
            });
            
            // Navigation
            $(document).on('click', '#atc-story-nav-prev', function() {
                self.previousStory();
            });
            
            $(document).on('click', '#atc-story-nav-next', function() {
                self.nextStory();
            });
            
            // Sound toggle
            $(document).on('click', '#atc-story-sound-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.soundEnabled = !self.soundEnabled;
                self.updateSoundToggle();
                self.applySoundState();
            });
            
            // Keyboard navigation
            $(document).on('keydown', function(e) {
                if (!self.viewer.hasClass('active')) return;
                
                if (e.key === 'Escape') {
                    self.closeViewer();
                } else if (e.key === 'ArrowLeft') {
                    self.previousStory();
                } else if (e.key === 'ArrowRight') {
                    self.nextStory();
                }
            });
            
            // Touch gestures
            self.viewer.on('touchstart', function(e) {
                self.touchStartX = e.originalEvent.touches[0].clientX;
                self.touchStartY = e.originalEvent.touches[0].clientY;
                self.pauseStory();
            });
            
            self.viewer.on('touchend', function(e) {
                self.touchEndX = e.originalEvent.changedTouches[0].clientX;
                self.touchEndY = e.originalEvent.changedTouches[0].clientY;
                self.handleSwipe();
                self.resumeStory();
            });
            
            // Pause on hover (desktop)
            self.viewer.on('mouseenter', function() {
                self.pauseStory();
            });
            
            self.viewer.on('mouseleave', function() {
                self.resumeStory();
            });
            
            // Click to advance (mobile)
            self.viewer.on('click', function(e) {
                if ($(e.target).closest('.atc-story-nav, .atc-story-content-button, .atc-story-nav-close').length) {
                    return;
                }
                
                var clickX = e.clientX || (e.originalEvent.touches && e.originalEvent.touches[0].clientX);
                var viewerWidth = self.viewer.width();
                
                if (clickX < viewerWidth / 3) {
                    self.previousStory();
                } else if (clickX > viewerWidth * 2 / 3) {
                    self.nextStory();
                }
            });
        },
        
        openStory: function(storyIndex, storiesData) {
            var self = this;
            
            var dataset = Array.isArray(storiesData) ? storiesData : self.getGlobalStories();
            
            if (!dataset || !Array.isArray(dataset) || dataset.length === 0) {
                console.error('ATC Stories: No stories data found');
                return;
            }
            
            self.stories = dataset;
            self.currentStoryIndex = parseInt(storyIndex, 10);
            if (isNaN(self.currentStoryIndex) || self.currentStoryIndex < 0) {
                self.currentStoryIndex = 0;
            }
            self.currentMediaIndex = 0;
            self.isPaused = false;
            
            if (self.currentStoryIndex >= self.stories.length) {
                self.currentStoryIndex = 0;
            }
            
            self.viewer.addClass('active');
            $('body').addClass('atc-story-viewer-open').css('overflow', 'hidden');
            self.updateSoundToggle();
            
            self.loadStory();
        },
        
        getStoriesFromItem: function($item) {
            var self = this;
            var $container = $item.closest('.atc-stories-container');
            
            if ($container.length) {
                var cached = $container.data('parsedStories');
                if (Array.isArray(cached) && cached.length) {
                    return cached;
                }
                
                var attrData = $container.attr('data-stories');
                var parsedAttr = self.parseStoriesJSON(attrData);
                if (parsedAttr) {
                    $container.data('parsedStories', parsedAttr);
                    return parsedAttr;
                }
                
                var $script = $container.find('script.atc-stories-data').first();
                if ($script.length) {
                    var parsedScript = self.parseStoriesJSON($script.html());
                    if (parsedScript) {
                        $container.data('parsedStories', parsedScript);
                        return parsedScript;
                    }
                }
            }
            
            return self.getGlobalStories();
        },
        
        parseStoriesJSON: function(jsonString) {
            if (!jsonString) {
                return null;
            }
            
            try {
                var parsed = JSON.parse(jsonString);
                return Array.isArray(parsed) ? parsed : null;
            } catch (error) {
                console.error('ATC Stories: Failed to parse stories JSON', error);
                return null;
            }
        },
        
        getGlobalStories: function() {
            var self = this;
            
            if (self.cachedGlobalStories && Array.isArray(self.cachedGlobalStories)) {
                return self.cachedGlobalStories;
            }
            
            var $script = $('script[id^="atc-stories-data-"]').first();
            if ($script.length) {
                var parsed = self.parseStoriesJSON($script.html());
                if (parsed) {
                    self.cachedGlobalStories = parsed;
                    return parsed;
                }
            }
            
            return null;
        },
        
        loadStory: function() {
            var self = this;
            var story = self.stories[self.currentStoryIndex];
            
            if (!story) {
                self.closeViewer();
                return;
            }
            
            if (story.media_type === 'video') {
                self.viewer.addClass('atc-story-has-video');
            } else {
                self.viewer.removeClass('atc-story-has-video');
            }
            self.updateSoundToggle();
            
            // Update progress bars
            self.updateProgressBars();
            
            // Load media
            var $mediaContainer = $('#atc-story-media-container');
            $mediaContainer.html('<div class="atc-story-loading">Loading...</div>');
            
            var mediaHTML = '';
            if (story.media_type === 'video') {
                mediaHTML = `<video class="atc-story-media" autoplay playsinline preload="auto"><source src="${story.media_url}" type="video/mp4"></video>`;
            } else {
                mediaHTML = `<img class="atc-story-media" src="${story.media_url}" alt="${story.title || 'Story'}" />`;
            }
            
            $mediaContainer.html(mediaHTML);
            
            // Load content overlay
            self.updateContentOverlay(story);
            
            // Start timer
            self.startTimer();
            
            // Handle video events
            var $video = $mediaContainer.find('video');
            if ($video.length > 0) {
                self.applySoundState($video);
                
                $video.on('loadeddata', function() {
                    self.startTimer();
                });
                
                $video.on('ended', function() {
                    self.nextStory();
                });
            }
        },
        
        applySoundState: function($videoElement) {
            var self = this;
            var videoEl = null;
            
            if ($videoElement && $videoElement.length) {
                videoEl = $videoElement[0];
            } else {
                var $video = $('#atc-story-media-container video');
                if ($video.length) {
                    videoEl = $video[0];
                }
            }
            
            if (videoEl) {
                videoEl.muted = !self.soundEnabled;
                videoEl.volume = self.soundEnabled ? 1 : 0;
                
                if (self.soundEnabled) {
                    var playPromise = videoEl.play();
                    if (playPromise && typeof playPromise.then === 'function') {
                        playPromise.catch(function(err) {
                            console.warn('ATC Stories: Unable to autoplay with sound', err);
                        });
                    }
                }
            }
        },
        
        updateSoundToggle: function() {
            var self = this;
            var $toggle = $('#atc-story-sound-toggle');
            
            if (!$toggle.length) return;
            
            if (self.soundEnabled) {
                $toggle.addClass('is-sound-on').removeClass('is-sound-off').attr('aria-label', 'Mute audio');
            } else {
                $toggle.addClass('is-sound-off').removeClass('is-sound-on').attr('aria-label', 'Unmute audio');
            }
        },
        
        updateProgressBars: function() {
            var self = this;
            var $progressContainer = $('#atc-story-progress-container');
            $progressContainer.empty();
            self.progressBars = [];
            
            self.stories.forEach(function(story, index) {
                var $bar = $('<div class="atc-story-progress-bar"><div class="atc-story-progress-fill"></div></div>');
                if (index === self.currentStoryIndex) {
                    $bar.addClass('active');
                }
                $progressContainer.append($bar);
                self.progressBars.push($bar);
            });
        },
        
        updateContentOverlay: function(story) {
            var self = this;
            var $content = $('#atc-story-content');
            var contentHTML = '';
            
            if (story.title) {
                contentHTML += `<h2 class="atc-story-content-title">${story.title}</h2>`;
            }
            
            if (story.package_description) {
                contentHTML += `<p class="atc-story-content-description">${story.package_description}</p>`;
            }
            
            if (story.package_price) {
                contentHTML += `<div class="atc-story-content-price">${story.package_price}</div>`;
            }
            
            if (story.package_link) {
                contentHTML += `<a href="${story.package_link}" class="atc-story-content-button" target="_blank">View Details</a>`;
            }
            
            $content.html(contentHTML);
        },
        
        startTimer: function() {
            var self = this;
            self.stopTimer();
            
            if (self.isPaused) return;
            
            var story = self.stories[self.currentStoryIndex];
            var duration = story.story_duration || 5000;
            
            var $currentBar = self.progressBars[self.currentStoryIndex];
            if ($currentBar.length) {
                $currentBar.find('.atc-story-progress-fill').css({
                    'animation-duration': duration + 'ms',
                    'width': '100%'
                });
            }
            
            self.timer = setTimeout(function() {
                self.nextStory();
            }, duration);
        },
        
        stopTimer: function() {
            var self = this;
            if (self.timer) {
                clearTimeout(self.timer);
                self.timer = null;
            }
            
            // Reset progress bars
            self.progressBars.forEach(function($bar) {
                $bar.find('.atc-story-progress-fill').css({
                    'animation': 'none',
                    'width': '0%'
                });
            });
        },
        
        pauseStory: function() {
            var self = this;
            self.isPaused = true;
            self.stopTimer();
            
            // Pause video if playing
            var $video = $('#atc-story-media-container video');
            if ($video.length > 0) {
                $video[0].pause();
            }
        },
        
        resumeStory: function() {
            var self = this;
            self.isPaused = false;
            
            // Resume video if paused
            var $video = $('#atc-story-media-container video');
            if ($video.length > 0) {
                $video[0].play().catch(function(e) {
                    console.log('Video autoplay prevented:', e);
                });
            }
            
            self.startTimer();
        },
        
        nextStory: function() {
            var self = this;
            self.currentStoryIndex++;
            
            if (self.currentStoryIndex >= self.stories.length) {
                self.closeViewer();
                return;
            }
            
            self.loadStory();
        },
        
        previousStory: function() {
            var self = this;
            self.currentStoryIndex--;
            
            if (self.currentStoryIndex < 0) {
                self.currentStoryIndex = 0;
            }
            
            self.loadStory();
        },
        
        closeViewer: function() {
            var self = this;
            self.stopTimer();
            self.viewer.removeClass('active');
            $('body').removeClass('atc-story-viewer-open').css('overflow', '');
            
            // Stop video
            var $video = $('#atc-story-media-container video');
            if ($video.length > 0) {
                $video[0].pause();
                $video[0].currentTime = 0;
            }
            
            // Clear media
            $('#atc-story-media-container').empty();
            $('#atc-story-content').empty();
        },
        
        handleSwipe: function() {
            var self = this;
            var swipeThreshold = 50;
            var diffX = self.touchStartX - self.touchEndX;
            var diffY = self.touchStartY - self.touchEndY;
            
            // Only handle horizontal swipes
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > swipeThreshold) {
                if (diffX > 0) {
                    // Swipe left - next story
                    self.nextStory();
                } else {
                    // Swipe right - previous story
                    self.previousStory();
                }
            }
        }
    };
    
    $(document).ready(function() {
        StoryViewer.init();
    });
    
})(jQuery);

