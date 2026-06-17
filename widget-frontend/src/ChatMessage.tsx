interface Props {
  role: 'user' | 'assistant';
  content: string;
  isStreaming?: boolean;
}

export default function ChatMessage({ role, content, isStreaming }: Props) {
  const isUser = role === 'user';

  return (
    <div className={`flex ${isUser ? 'justify-end' : 'justify-start'} mb-3`}>
      {!isUser && (
        <div
          className="w-7 h-7 rounded-full flex items-center justify-center mr-2 flex-shrink-0 mt-0.5 text-white text-xs font-bold"
          style={{ backgroundColor: 'var(--acw-primary)' }}
        >
          AI
        </div>
      )}
      <div
        className={`rounded-2xl px-4 py-2.5 text-sm leading-relaxed ${
          isUser
            ? 'text-white rounded-br-sm'
            : 'bg-gray-100 text-gray-800 rounded-bl-sm'
        }`}
        style={{
          maxWidth: '80%',
          backgroundColor: isUser ? 'var(--acw-primary)' : undefined,
        }}
      >
        <span className="whitespace-pre-wrap break-words">{content}</span>
        {isStreaming && (
          <span
            className="inline-block w-0.5 h-3.5 ml-0.5 align-middle animate-pulse rounded-sm"
            style={{ backgroundColor: isUser ? 'rgba(255,255,255,0.8)' : 'var(--acw-primary)' }}
          />
        )}
      </div>
    </div>
  );
}
