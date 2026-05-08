<?php

namespace App\Services;

use App\Models\VirtualClassroom;
use Illuminate\Support\Facades\Auth;

class JitsiMeetingService
{
    /**
     * Jitsi Meet domain
     */
    protected string $domain;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Default to public Jitsi server, can be configured for self-hosted
        $this->domain = config('services.jitsi.domain', 'meet.jit.si');
    }

    /**
     * Generate a unique room name for a virtual classroom
     *
     * @param VirtualClassroom $virtualClassroom
     * @return string
     */
    public function generateRoomName(VirtualClassroom $virtualClassroom): string
    {
        return VirtualClassroom::generateRoomName(
            $virtualClassroom->school_id,
            $virtualClassroom->title
        );
    }

    /**
     * Build the meeting URL
     *
     * @param string $roomName
     * @return string
     */
    public function buildMeetingUrl(string $roomName): string
    {
        return "https://{$this->domain}/{$roomName}";
    }

    /**
     * Generate JWT token for moderator access (for self-hosted Jitsi)
     *
     * @param VirtualClassroom $virtualClassroom
     * @param User $user
     * @return string|null
     */
    public function generateModeratorToken(VirtualClassroom $virtualClassroom, $user): ?string
    {
        // For self-hosted Jitsi with JWT authentication
        // This is a placeholder for future implementation
        if (!config('services.jitsi.jwt_enabled', false)) {
            return null;
        }

        // JWT implementation would go here
        // $payload = [
        //     'iss' => config('services.jitsi.app_id'),
        //     'aud' => config('services.jitsi.app_id'),
        //     'sub' => $this->domain,
        //     'room' => $virtualClassroom->room_name,
        //     'moderator' => true,
        //     'context' => [
        //         'user' => [
        //             'name' => $user->full_name,
        //             'email' => $user->email,
        //             'avatar' => $user->image ?? null,
        //         ]
        //     ]
        // ];
        // return jwt_encode($payload, config('services.jitsi.secret'));

        return null;
    }

    /**
     * Get Jitsi External API configuration for embedding
     *
     * @param VirtualClassroom $virtualClassroom
     * @param User $user
     * @param bool $isModerator
     * @return array
     */
    public function getMeetingConfig(VirtualClassroom $virtualClassroom, $user, bool $isModerator = false): array
    {
        $roomName = $virtualClassroom->room_name;
        $displayName = $user->full_name ?? $user->first_name . ' ' . $user->last_name;
        $email = $user->email ?? '';

        return [
            'domain' => $this->domain,
            'roomName' => $roomName,
            'width' => '100%',
            'height' => '100%',
            'parentNode' => 'jitsi-meeting-container',
            'configOverwrite' => [
                'prejoinPageEnabled' => false,
                'startWithAudioMuted' => false,
                'startWithVideoMuted' => false,
                'disableDeepLinking' => true,
                'enableClosePage' => false,
            ],
            'interfaceConfigOverwrite' => [
                'SHOW_JITSI_WATERMARK' => false,
                'SHOW_WATERMARK_FOR_GUESTS' => false,
                'DEFAULT_BACKGROUND' => '#3c4043',
                'TOOLBAR_BUTTONS' => [
                    'microphone',
                    'camera',
                    'desktop',
                    'fullscreen',
                    'fodeviceselection',
                    'hangup',
                    'profile',
                    'chat',
                    'recording',
                    'livestreaming',
                    'etherpad',
                    'sharedvideo',
                    'settings',
                    'raisehand',
                    'videoquality',
                    'filmstrip',
                    'invite',
                    'feedback',
                    'stats',
                    'shortcuts',
                    'tileview',
                    'videobackgroundblur',
                    'download',
                    'help',
                    'mute-everyone',
                    'mute-video-everyone',
                ],
            ],
            'userInfo' => [
                'displayName' => $displayName,
                'email' => $email,
            ],
            'onReadyToClose' => 'handleMeetingClose',
            'jwt' => $isModerator ? $this->generateModeratorToken($virtualClassroom, $user) : null,
        ];
    }

    /**
     * Get the Jitsi External API script URL
     *
     * @return string
     */
    public function getExternalApiUrl(): string
    {
        return "https://{$this->domain}/external_api.js";
    }

    /**
     * Check if meeting is currently active
     *
     * @param VirtualClassroom $virtualClassroom
     * @return bool
     */
    public function isMeetingActive(VirtualClassroom $virtualClassroom): bool
    {
        return $virtualClassroom->status === 'live' ||
            ($virtualClassroom->start_time <= now() &&
             $virtualClassroom->end_time >= now());
    }

    /**
     * Check if user can join as moderator
     *
     * @param VirtualClassroom $virtualClassroom
     * @param User $user
     * @return bool
     */
    public function canModerate(VirtualClassroom $virtualClassroom, $user): bool
    {
        // Check if user is the assigned teacher or has admin role
        return $virtualClassroom->teacher_id === $user->id ||
            $user->hasRole('School Admin') ||
            $user->hasRole('Super Admin');
    }

    /**
     * Get formatted meeting time
     *
     * @param VirtualClassroom $virtualClassroom
     * @return array
     */
    public function getMeetingTimes(VirtualClassroom $virtualClassroom): array
    {
        return [
            'start_formatted' => $virtualClassroom->start_time->format('M d, Y h:i A'),
            'end_formatted' => $virtualClassroom->end_time->format('M d, Y h:i A'),
            'duration_minutes' => $virtualClassroom->start_time->diffInMinutes($virtualClassroom->end_time),
            'is_now' => now()->between($virtualClassroom->start_time, $virtualClassroom->end_time),
            'starts_in' => now()->isBefore($virtualClassroom->start_time)
                ? now()->diffForHumans($virtualClassroom->start_time, true) . ' left'
                : null,
        ];
    }

    /**
     * Set domain for self-hosted Jitsi
     *
     * @param string $domain
     * @return self
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }
}
