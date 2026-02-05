import React, { useState, useEffect, useRef } from 'react';
import { useChat } from '../hooks/useApi';
import { FiSend } from 'react-icons/fi';
import { TailSpin } from 'react-loading-icons';

const escapeHtml = (text) => {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  };
  return String(text).replace(/[&<>"']/g, (m) => map[m]);
};

const MessageList = ({ roomId, aiProvider = 'openai' }) => {
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(false);
  const [goalMode, setGoalMode] = useState(false);
  const [goalText, setGoalText] = useState('');
  const [goalNotes, setGoalNotes] = useState([]);
  const [savingGoal, setSavingGoal] = useState(false);
  const [isBotThinking, setIsBotThinking] = useState(false);
  const [editingGoalId, setEditingGoalId] = useState(null); // 編集中の目標ID
  const { getRoom, sendMessage, deleteMessage, likeMessage, createGoalNote, updateGoalNote, deleteGoalNote, getGoalNotes } = useChat();
  const messagesEndRef = useRef(null);
  const lastMessageIdRef = useRef(null);
  const shouldScrollRef = useRef(null); // スクロール種類: 'bottom'=最下部, 'lastUserMessage'=最新質問
  const lastUserMessageRef = useRef(null); // 最新のユーザーメッセージへの参照

  const loadMessages = async () => {
    setLoading(true);
    try {
      const result = await getRoom(roomId);
      console.log('Loaded messages:', result.data.messages);
      // 直近50件のみ表示
      const allMessages = result.data.messages || [];
      setMessages(allMessages.slice(-50));
      shouldScrollRef.current = 'bottom'; // ルーム選択時は最下部へスクロール
    } catch (err) {
      console.error('Failed to load messages:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadGoalNotes = async () => {
    if (!roomId) return;
    try {
      const result = await getGoalNotes(roomId);
      const notes = Array.isArray(result.data) ? result.data : [];
      setGoalNotes(notes.slice(0, 1));
    } catch (err) {
      console.error('Failed to load goal notes:', err);
    }
  };

  useEffect(() => {
    if (roomId) {
      loadMessages();
      loadGoalNotes();
      lastMessageIdRef.current = null;
    }
  }, [roomId]);

  useEffect(() => {
    // メッセージが変更されたときに適切な位置へスクロール
    if (messages.length > 0 && shouldScrollRef.current) {
      const scrollType = shouldScrollRef.current;
      // 次のフレームでスクロール（DOMの更新を待つ）
      setTimeout(() => {
        if (scrollType === 'bottom') {
          // 最下部へスクロール
          messagesEndRef.current?.scrollIntoView({ behavior: 'auto', block: 'end' });
        } else if (scrollType === 'lastUserMessage') {
          // 最新のユーザーメッセージまでスクロール
          lastUserMessageRef.current?.scrollIntoView({ behavior: 'auto', block: 'start' });
        }
        shouldScrollRef.current = null; // フラグをリセット
      }, 0);
    }
  }, [messages]);

  const handleSendMessage = async (messageText) => {
    if (!messageText.trim()) return;

    try {
      setIsBotThinking(true);
      const history = messages.map((m) => ({
        role: m.is_ai ? 'assistant' : 'user',
        content: m.content,
      }));

      await sendMessage(messageText, roomId, history, aiProvider);
      shouldScrollRef.current = 'lastUserMessage'; // 新規メッセージ送信時は最新質問の位置へスクロール
      await loadMessages();
    } catch (err) {
      console.error('Failed to send message:', err);
    } finally {
      setIsBotThinking(false);
    }
  };

  const handleSaveGoal = async () => {
    if (!goalMode) return;
    if (!goalText.trim()) {
      alert('達成状況のテキストを入力してください');
      return;
    }
    setSavingGoal(true);
    try {
      if (editingGoalId) {
        // 既存の目標を更新
        await updateGoalNote(editingGoalId, goalText.trim());
        alert('目標達成メモを更新しました');
        setEditingGoalId(null);
      } else {
        // 新規作成
        await createGoalNote(roomId, goalText.trim(), null);
        alert('目標達成メモを保存しました');
      }
      setGoalText('');
      await loadGoalNotes();
    } catch (err) {
      console.error('Failed to save goal note:', err);
      alert('保存に失敗しました: ' + err.message);
    } finally {
      setSavingGoal(false);
    }
  };

  const handleDeleteMessage = async (messageId) => {
    if (!window.confirm('このメッセージを削除しますか？')) return;

    try {
      await deleteMessage(roomId, messageId);
      // 全体再読み込みせず、該当メッセージだけ削除
      setMessages(messages.filter(m => m.id !== messageId));
    } catch (err) {
      console.error('Failed to delete message:', err);
    }
  };

  const handleToggleLike = async (messageId, liked) => {
    try {
      await likeMessage(roomId, messageId, !liked);
      // スクロール位置を保持したまま該当メッセージだけ更新
      setMessages(messages.map(m => 
        m.id === messageId 
          ? { ...m, liked_by_me: !liked, like_count: (m.like_count || 0) + (liked ? -1 : 1) }
          : m
      ));
    } catch (err) {
      console.error('Failed to like message:', err);
    }
  };

  if (loading) {
    return <div className="messages-container">読み込み中...</div>;
  }

  if (!roomId) {
    return <div className="messages-container">ルームが選択されていません</div>;
  }

  return (
    <>
      <div className="messages-container">
        {messages.length === 0 ? (
          <div style={{ padding: '20px', textAlign: 'center', color: '#999' }}>
            まだメッセージがありません
          </div>
        ) : (
          messages.map((msg, index) => {
            // 最後のユーザーメッセージかどうかを判定
            const isLastUserMessage = !msg.is_ai && 
              index === messages.map((m, i) => !m.is_ai ? i : -1).filter(i => i !== -1).pop();
            
            return (
            <div
              key={msg.id}
              ref={isLastUserMessage ? lastUserMessageRef : null}
              className={`message ${msg.is_ai ? 'bot-message' : 'user-message'}`}
            >
              <div className="message-content-row">
                <div 
                  className="message-content"
                  dangerouslySetInnerHTML={{ 
                    __html: escapeHtml(msg.content).replace(/\n/g, '<br>') 
                  }}
                />
                <div className="message-actions" style={{border: '2px solid red'}}>
                  <button
                    className="msg-delete-btn"
                    onClick={() => handleDeleteMessage(msg.id)}
                    title="削除"
                    type="button"
                  >
                    ×
                  </button>
                </div>
              </div>
              <div className="message-like-area">
                <button
                  className={`msg-like-btn ${msg.liked_by_me ? 'liked' : ''}`}
                  onClick={() => handleToggleLike(msg.id, msg.liked_by_me)}
                  title={msg.liked_by_me ? 'いいねを取り消す' : 'いいね'}
                  type="button"
                >
                  <span className="msg-like-icon">❤</span>
                  <span className="msg-like-count">{msg.like_count || 0}</span>
                </button>
              </div>
            </div>
            );
          })
        )}
        {isBotThinking && (
          <div className="bot-thinking">
            <TailSpin stroke="#1967d2" width={24} height={24} />
          </div>
        )}
        <div ref={messagesEndRef} />
      </div>
      {goalNotes.length > 0 && (
        <div className="goal-note-list">
          <div className="goal-note-title">このルームの目標達成メモ</div>
          {goalNotes.map((note) => (
            <div 
              key={note.id} 
              className="goal-note-item"
              onClick={() => {
                setGoalText(note.note_text);
                setEditingGoalId(note.id);
                setGoalMode(true);
              }}
            >
              <div className="goal-note-text">{note.note_text}</div>
              <div className="goal-note-meta">
                {new Date(note.created_at).toLocaleString()}
              </div>
            </div>
          ))}
        </div>
      )}
      <div className="message-input-area">
        <div className="goal-mode-panel">
          <label className="goal-mode-toggle">
            <input
              type="checkbox"
              checked={goalMode}
              onChange={(e) => setGoalMode(e.target.checked)}
            />
            目標達成を考察する （ドキドキ ワクワク トキメキをイメージできる目標へ）
          </label>
          {goalMode && (
            <div className="goal-mode-body">
              <textarea
                value={goalText}
                onChange={(e) => setGoalText(e.target.value)}
                placeholder="この会話で得た気付きや達成状況をメモしてください"
                rows={3}
              />
              <div className="goal-mode-actions">
                <button
                  type="button"
                  className="btn btn-sm btn-primary"
                  onClick={handleSaveGoal}
                  disabled={savingGoal || !goalText.trim()}
                >
                  目標達成として保存
                </button>
                <button
                  type="button"
                  className="btn btn-sm"
                  onClick={() => setGoalText('')}
                  disabled={savingGoal}
                >
                  クリア
                </button>
              </div>
            </div>
          )}
        </div>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            const input = e.target.elements.messageInput;
            handleSendMessage(input.value);
            input.value = '';
            input.focus();
          }}
          id="messageForm"
        >
          <input
            type="text"
            id="messageInput"
            placeholder="メッセージを入力..."
            required
          />
          <button
            type="submit"
            className="icon-button"
            aria-label="送信"
            title="送信"
          >
            <FiSend size={20} />
          </button>
        </form>
      </div>
    </>
  );
};

export default MessageList;
