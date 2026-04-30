<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-main']) }}>
    {{ $slot }}
</button>
