@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>لوحة المراجع</h1>
        <p>تفاسير بانتظار المراجعة: <strong>{{ $stats['pending_tafsir'] }}</strong></p>
        <p>أحاديث بانتظار المراجعة: <strong>{{ $stats['pending_hadith'] }}</strong></p>
        <p>أقوال علماء بانتظار المراجعة: <strong>{{ $stats['pending_commentary'] }}</strong></p>
        <p>الأسماء المعتمدة: <strong>{{ $stats['reviewed_names'] }} / {{ $stats['total_names'] }}</strong></p>
    </div>

    <div class="card">
        <h2>قائمة المراجعة</h2>
        @forelse($queue as $item)
            <form class="card" method="POST" action="{{ route('admin.review') }}">
                @csrf
                <input type="hidden" name="content_type" value="{{ $item->content_type }}">
                <input type="hidden" name="content_id" value="{{ $item->id }}">
                <strong>
                    @if($item->content_type === 'tafsir_entries') تفسير
                    @elseif($item->content_type === 'hadiths') حديث
                    @else قول عالم
                    @endif
                    #{{ $item->id }} — {{ $item->title }}
                </strong>
                <p style="direction:rtl; line-height:1.9; font-size:1.05rem;">{{ $item->text }}</p>
                <label>تعديل النص (اختياري عند الاعتماد مع تعديل)</label>
                <textarea name="edited_text" rows="3"></textarea>
                <label>ملاحظات</label>
                <textarea name="notes" rows="2"></textarea>
                <label>القرار</label>
                <select name="action">
                    <option value="approve">اعتماد</option>
                    <option value="approve_with_edit">اعتماد مع تعديل</option>
                    <option value="reject">رفض</option>
                </select>
                <button class="btn" type="submit">حفظ القرار</button>
            </form>
        @empty
            <p>لا توجد إدخالات بانتظار المراجعة.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>إضافة تعليق الوالد</h2>
        <form method="POST" action="{{ route('admin.narrative') }}">
            @csrf
            <label>رقم الاسم</label>
            <input type="number" name="name_id" required>
            <label>رقم الآية (اختياري)</label>
            <input type="number" name="verse_id">
            <label>نص التعليق</label>
            <textarea name="narrative_text" rows="5" required></textarea>
            <label>الحالة</label>
            <select name="status">
                <option value="draft">مسودة</option>
                <option value="final">نهائي</option>
            </select>
            <button class="btn" type="submit">إضافة التعليق</button>
        </form>
    </div>
@endsection
