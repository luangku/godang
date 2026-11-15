@foreach ($replies as $reply)
    <div class="mt-4 ml-4 pl-4 border-l-2 border-(--color-accent)/20" id="comment-{{ $reply->id }}"
        data-created-at="{{ $reply->created_at->timestamp }}">
        <div class="flex items-start gap-2">
            <div
                class="w-8 h-8 rounded-full bg-gradient-to-br from-(--color-accent-bg) to-(--color-accent)/20 flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-(--color-accent)">
                    {{ strtoupper(substr($reply->name, 0, 1)) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="font-semibold text-sm">{{ $reply->name }}</span>
                    @if ($reply->is_author ?? false)
                        <span
                            class="text-xs px-2 py-0.5 rounded-md bg-(--color-accent) text-(--color-on-accent) font-medium">
                            Penulis
                        </span>
                    @endif
                    @if ($reply->reply_to_name)
                        <span class="text-xs text-(--color-text-muted)">
                            membalas ke {{ '@' . $reply->reply_to_name }}
                        </span>
                    @endif
                    <time class="text-xs text-(--color-text-muted)" datetime="{{ $reply->created_at }}">
                        {{ \Carbon\Carbon::parse($reply->created_at)->diffForHumans() }}
                    </time>
                </div>
                <p class="comment-body text-sm text-(--color-text) leading-relaxed">{{ $reply->body }}</p>

                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <button type="button"
                        class="reply-toggle inline-flex items-center gap-1 text-xs font-medium text-(--color-accent) hover:underline cursor-pointer"
                        data-comment-id="{{ $reply->id }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Balas
                    </button>
                    @if ($isAdmin ?? false)
                        @if ($reply->is_author ?? false)
                            <button type="button"
                                class="comment-admin-edit inline-flex items-center gap-1 text-xs font-medium text-(--color-accent) hover:underline cursor-pointer"
                                data-comment-id="{{ $reply->id }}">
                                Edit
                            </button>
                        @endif
                        <button type="button"
                            class="comment-admin-spam inline-flex items-center gap-1 text-xs font-medium text-(--color-warning) hover:underline cursor-pointer"
                            data-comment-id="{{ $reply->id }}">
                            Spam
                        </button>
                        <button type="button"
                            class="comment-admin-delete inline-flex items-center gap-1 text-xs font-medium text-(--color-error) hover:underline cursor-pointer"
                            data-comment-id="{{ $reply->id }}">
                            Hapus
                        </button>
                    @endif
                </div>

                <div class="reply-form-container hidden mt-3" id="reply-form-{{ $reply->id }}">
                    <form action="{{ url('/api/posts/' . $post->id . '/comments') }}" method="POST"
                        class="reply-form bg-(--color-bg) rounded-lg border border-(--color-border) p-3">
                        @csrf
                        <div class="hidden" aria-hidden="true">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>
                        <input type="hidden" name="parent_id" value="{{ $reply->id }}">

                        @unless ($isAdmin ?? false)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                                <div>
                                    <label class="block text-xs font-medium mb-1">
                                        Nama <span
                                            class="text-(--color-error)">*</span>
                                    </label>
                                    <input type="text" name="name" required
                                        class="w-full px-2.5 py-1.5 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">
                                        Email <span class="text-(--color-error)">*</span>
                                    </label>
                                    <input type="email" name="email" required
                                        class="w-full px-2.5 py-1.5 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none">
                                </div>
                            </div>
                        @endunless
                        <div class="mb-2">
                            <label class="block text-xs font-medium mb-1">
                                Balasan <span
                                    class="text-(--color-error)">*</span>
                            </label>
                            <textarea name="body" rows="2" required
                                class="w-full px-2.5 py-1.5 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm text-(--color-text) focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none resize-y"></textarea>
                        </div>
                        {{--
                        Notifikasi balasan untuk pembaca (field notify_replies).
                        Checkbox "Kirim notifikasi jika ada balasan" sengaja disembunyikan, namun notifikasi
                        tetap terkirim otomatis karena input hidden di bawah selalu bernilai 1.

                        CARA MENAMPILKAN KEMBALI CHECKBOX:
                        1. Hapus input hidden "notify_replies" di bawah.
                        2. Hapus tanda buka dan tutup komentar Blade pada blok di bawah ini
                        (bagian div yang berisi checkbox), agar checkbox kembali tampil.
                    --}}
                        <input type="hidden" name="notify_replies" value="1">
                        {{-- <div class="mb-2 flex items-center gap-2">
                        <input type="checkbox" name="notify_replies" value="1" class="rounded border-(--color-border) text-(--color-accent) focus:ring-(--color-accent)">
                        <label class="text-xs text-(--color-text) flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A3.032 3.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Kirim notifikasi jika ada balasan
                        </label>
                    </div> --}}
                        <div class="flex items-center gap-2">
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-medium text-xs hover:opacity-90">
                                Kirim
                            </button>
                            <button type="button"
                                class="reply-cancel px-2.5 py-1.5 rounded-lg border border-(--color-border) text-xs text-(--color-text-muted) hover:text-(--color-text)">
                                Batal
                            </button>
                        </div>
                        <div class="reply-status hidden mt-1.5 text-xs"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
