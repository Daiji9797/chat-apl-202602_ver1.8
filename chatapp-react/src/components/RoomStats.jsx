import React, { useState, useEffect } from 'react';
import { useRooms, useChat } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';
import { api } from '../services/api';
import './RoomStats.css';

const RoomStats = ({ onBackToChat }) => {
  const { rooms, loadRooms, updateRoom } = useRooms();
  const { getRoom, getGoalNotes, createGoalNote, updateGoalNote } = useChat();
  const { user } = useAuth();
  const [roomStats, setRoomStats] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedRoom, setSelectedRoom] = useState(null);
  const [roomDetailData, setRoomDetailData] = useState(null);
  const [showMessage, setShowMessage] = useState(false);
  const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });
  const [sortBy, setSortBy] = useState('questions'); // 'questions' or 'recent'
  const [selectedGoalRoom, setSelectedGoalRoom] = useState(null); // { roomId, roomName, text, id }
  const [todayTopics, setTodayTopics] = useState([]);
  const [topicsLoading, setTopicsLoading] = useState(false);
  const [showAllTopics, setShowAllTopics] = useState(false);

  useEffect(() => {
    loadRoomStats();
    loadTodayTopics();
    
    // 寄り添い画像が設定されている場合のみメッセージを表示
    if (user?.stalker_image) {
      setShowMessage(true);
      
      // 5秒後にメッセージを非表示
      const timer = setTimeout(() => {
        setShowMessage(false);
      }, 5000);

      return () => clearTimeout(timer);
    }
  }, []);

  // マウスカーソルの位置を追跡
  useEffect(() => {
    const handleMouseMove = (e) => {
      setMousePosition({ x: e.clientX, y: e.clientY });
    };

    window.addEventListener('mousemove', handleMouseMove);
    return () => window.removeEventListener('mousemove', handleMouseMove);
  }, []);

  const loadRoomStats = async () => {
    setLoading(true);
    try {
      const roomsData = await loadRooms();
      console.log('[RoomStats] Loaded rooms data:', roomsData);
      
      // Process rooms sequentially with a small delay to avoid overwhelming the server
      const stats = [];
      for (let i = 0; i < roomsData.length; i++) {
        const room = roomsData[i];
        try {
          // Add a small delay between requests to prevent network issues
          if (i > 0) {
            await new Promise(resolve => setTimeout(resolve, 100));
          }
          
          const [roomDetail, goalNotes] = await Promise.all([
            getRoom(room.id),
            getGoalNotes(room.id),
          ]);

          const messages = roomDetail.data.messages || [];
          const userMessages = messages.filter(m => !m.is_ai);  // is_ai が false がユーザーメッセージ
          const likedMessages = messages.filter(m => m.liked_by_me);
          const goals = Array.isArray(goalNotes.data) ? goalNotes.data : [];
          
          console.log(`[RoomStats] Room ${room.id} (${room.name}): ${messages.length} total messages, ${userMessages.length} user messages`);
          
          // 最新の目標を取得（最初の1件）
          const latestGoal = goals.length > 0 ? goals[0].note_text : '';
          const latestGoalId = goals.length > 0 ? goals[0].id : null;

          stats.push({
            id: room.id,
            name: room.name,
            created_at: room.created_at,
            is_completed: room.is_completed || false,
            goal_text: latestGoal,
            goal_id: latestGoalId,
            userMessages: userMessages.length,
            totalMessages: messages.length,
            likedMessages: likedMessages.length,
            goalCount: goals.length,
            lastActivity: messages.length > 0 
              ? new Date(messages[messages.length - 1].created_at)
              : new Date(room.created_at),
          });
        } catch (err) {
          console.error(`Failed to load stats for room ${room.id}:`, err);
          stats.push({
            id: room.id,
            name: room.name,
            created_at: room.created_at,
            is_completed: room.is_completed || false,
            goal_text: '',
            goal_id: null,
            userMessages: 0,
            totalMessages: 0,
            likedMessages: 0,
            goalCount: 0,
            lastActivity: new Date(room.created_at),
          });
        }
      }

      console.log('[RoomStats] Final stats:', stats);
      stats.sort((a, b) => b.lastActivity - a.lastActivity);
      setRoomStats(stats);
    } catch (err) {
      console.error('Failed to load room stats:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadTodayTopics = async () => {
    setTopicsLoading(true);
    try {
      console.log('[RoomStats] Loading weekly topics ranking...');
      const response = await api.getWeeklyTopicsRanking();
      console.log('[RoomStats] Weekly topics response:', response);
      
      if (response.success && Array.isArray(response.data)) {
        console.log('[RoomStats] Setting topics:', response.data);
        setTodayTopics(response.data);
      } else {
        console.warn('[RoomStats] Unexpected response format:', response);
        setTodayTopics([]);
      }
    } catch (err) {
      console.error('[RoomStats] Failed to load weekly topics ranking:', err);
      setTodayTopics([]);
    } finally {
      setTopicsLoading(false);
    }
  };

  const toggleRoomCompletion = async (roomId, currentStatus) => {
    try {
      await updateRoom(roomId, { is_completed: !currentStatus });
      setRoomStats(prev => 
        prev.map(room => 
          room.id === roomId 
            ? { ...room, is_completed: !currentStatus }
            : room
        )
      );
    } catch (err) {
      console.error('Failed to update room completion status:', err);
      alert('完了状態の更新に失敗しました');
    }
  };

  const handleGoalClick = (roomId, roomName, currentText, goalId) => {
    setSelectedGoalRoom({ roomId, roomName, text: currentText, id: goalId });
  };

  const handleGoalSave = async () => {
    if (!selectedGoalRoom) return;
    
    try {
      if (selectedGoalRoom.id) {
        // 既存の目標を更新
        await updateGoalNote(selectedGoalRoom.id, selectedGoalRoom.text);
      } else {
        // 新規作成
        await createGoalNote(selectedGoalRoom.roomId, selectedGoalRoom.text);
      }
      
      // ルーム統計を再読み込み
      await loadRoomStats();
      setSelectedGoalRoom(null);
    } catch (err) {
      console.error('Failed to update goal:', err);
      alert('目標の更新に失敗しました');
    }
  };

  const handleGoalCancel = () => {
    setSelectedGoalRoom(null);
  };

  const handleRoomClick = async (roomId) => {
    try {
      const roomDetail = await getRoom(roomId);
      const messages = roomDetail.data.messages || [];
      
      // 日付ごとに質問数を集計（AIではなくユーザーメッセージのみ）
      const dailyStats = {};
      messages.forEach(msg => {
        if (!msg.is_ai) {
          const date = new Date(msg.created_at).toLocaleDateString('ja-JP');
          dailyStats[date] = (dailyStats[date] || 0) + 1;
        }
      });

      // 日付順にソート
      const sortedDates = Object.keys(dailyStats).sort((a, b) => {
        return new Date(a) - new Date(b);
      });

      const chartData = sortedDates.map(date => ({
        date,
        count: dailyStats[date]
      }));

      const roomInfo = roomStats.find(r => r.id === roomId);
      setSelectedRoom(roomInfo);
      setRoomDetailData(chartData);
    } catch (err) {
      console.error('Failed to load room details:', err);
    }
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleString('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getTotalStats = () => {
    return {
      totalRooms: roomStats.length,
      completedRooms: roomStats.filter(r => r.is_completed).length,
      totalMessages: roomStats.reduce((sum, r) => sum + r.totalMessages, 0),
      totalUserMessages: roomStats.reduce((sum, r) => sum + r.userMessages, 0),
      totalGoals: roomStats.reduce((sum, r) => sum + r.goalCount, 0),
    };
  };

  if (loading) {
    return (
      <div className="stats-container">
        <div className="stats-loading">統計情報を読み込み中...</div>
      </div>
    );
  }

  const totalStats = getTotalStats();

  // グラフ用データを準備
  const top5Rooms = roomStats
    .sort((a, b) => {
      if (sortBy === 'questions') {
        return b.userMessages - a.userMessages;
      } else {
        return b.lastActivity - a.lastActivity;
      }
    })
    .slice(0, 5);

  const maxUserMessages = Math.max(...top5Rooms.map(r => r.userMessages), 1);

  // 折れ線グラフの最大値
  const maxCount = roomDetailData ? Math.max(...roomDetailData.map(d => d.count), 1) : 1;

  return (
    <div className="stats-container">
      {showMessage && (
        <div 
          className={`encouragement-message ${!showMessage ? 'fade-out' : ''}`}
          style={{
            left: `${mousePosition.x + 40}px`,
            top: `${mousePosition.y - 80}px`
          }}
        >
          <div className="encouragement-bubble">
            <span className="encouragement-text">いつも頑張ってるね！✨</span>
          </div>
        </div>
      )}
      
      <div className="stats-header">
        <h1>ルーム利用状況</h1>
        <button className="btn btn-primary" onClick={onBackToChat}>
          チャットに戻る
        </button>
      </div>

      <div className="stats-content-scroll">
      {/* 目標編集モーダル */}
      {selectedGoalRoom && (
        <div className="room-detail-modal">
          <div className="room-detail-content goal-modal">
            <div className="room-detail-header">
              <h2>{selectedGoalRoom.roomName} - 目標編集</h2>
              <button className="btn-close" onClick={handleGoalCancel}>
                ×
              </button>
            </div>
            <div className="goal-edit-section">
              <label className="goal-edit-label">目標内容</label>
              <textarea
                className="goal-edit-textarea"
                value={selectedGoalRoom.text}
                onChange={(e) => setSelectedGoalRoom({ ...selectedGoalRoom, text: e.target.value })}
                placeholder="目標を入力してください..."
                rows="8"
                autoFocus
              />
            </div>
            <div className="modal-actions">
              <button className="btn btn-primary" onClick={handleGoalSave}>
                保存
              </button>
              <button className="btn btn-secondary" onClick={handleGoalCancel}>
                キャンセル
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ルーム詳細モーダル */}
      {selectedRoom && roomDetailData && (
        <div className="room-detail-modal">
          <div className="room-detail-content">
            <div className="room-detail-header">
              <h2>{selectedRoom.name} の利用推移</h2>
              <button 
                className="btn-close"
                onClick={() => {
                  setSelectedRoom(null);
                  setRoomDetailData(null);
                }}
              >
                ×
              </button>
            </div>
            <div className="line-chart-container">
              <div className="line-chart">
                <div className="line-chart-grid">
                  {[...Array(5)].map((_, i) => {
                    const value = Math.round((maxCount / 4) * (4 - i));
                    return (
                      <div key={i} className="grid-line">
                        <span className="grid-label">{value}</span>
                      </div>
                    );
                  })}
                </div>
                <svg className="line-chart-svg" viewBox="0 0 600 300" preserveAspectRatio="none">
                  <polyline
                    points={roomDetailData.map((d, i) => {
                      const x = (i / (roomDetailData.length - 1 || 1)) * 600;
                      const y = 300 - (d.count / maxCount) * 280;
                      return `${x},${y}`;
                    }).join(' ')}
                    fill="none"
                    stroke="#1967d2"
                    strokeWidth="3"
                  />
                  {roomDetailData.map((d, i) => {
                    const x = (i / (roomDetailData.length - 1 || 1)) * 600;
                    const y = 300 - (d.count / maxCount) * 280;
                    return (
                      <circle
                        key={i}
                        cx={x}
                        cy={y}
                        r="4"
                        fill="#1967d2"
                      />
                    );
                  })}
                </svg>
              </div>
              <div className="line-chart-labels">
                {roomDetailData.map((d, i) => (
                  <div key={i} className="chart-label">
                    <div className="label-date">{d.date}</div>
                    <div className="label-count">{d.count}件</div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      <div className="stats-summary">
        <div className="stats-summary-card">
          <div className="stats-summary-label">総ルーム数</div>
          <div className="stats-summary-value">{totalStats.totalRooms}</div>
        </div>
        <div className="stats-summary-card">
          <div className="stats-summary-label">完了済み</div>
          <div className="stats-summary-value completed">{totalStats.completedRooms}</div>
        </div>
        <div className="stats-summary-card">
          <div className="stats-summary-label">総質問数</div>
          <div className="stats-summary-value">{totalStats.totalUserMessages}</div>
        </div>
        <div className="stats-summary-card">
          <div className="stats-summary-label">目標設定数</div>
          <div className="stats-summary-value goals">{totalStats.totalGoals}</div>
        </div>
      </div>

      <div className="stats-charts">
        <div className="chart-container" style={{ marginBottom: '12px' }}>
          <div className="chart-header">
            <h3 className="chart-title">📊 この1週間のテーマランキング</h3>
            <div className="chart-sort-buttons">
              <button
                className="chart-sort-btn"
                onClick={() => setShowAllTopics(prev => !prev)}
                title={showAllTopics ? '少なく表示' : 'もっと見る'}
              >
                {showAllTopics ? '少なく表示' : 'もっと見る'}
              </button>
            </div>
          </div>
          <div className="today-topics-ranking">
            {topicsLoading ? (
              <div className="ranking-loading">読み込み中...</div>
            ) : todayTopics.length > 0 ? (
              <div className="ranking-list">
                {(showAllTopics ? todayTopics.slice(0, 5) : todayTopics.slice(0, 3)).map((item) => (
                  <div key={item.rank} className="ranking-item">
                    <div className="ranking-badge">
                      {item.rank === 1 && '🥇'}
                      {item.rank === 2 && '🥈'}
                      {item.rank === 3 && '🥉'}
                      {item.rank > 3 && <span className="rank-number">{item.rank}</span>}
                    </div>
                    <div className="ranking-theme">{item.theme}</div>
                    <div className="ranking-count">{item.count}件</div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="ranking-empty">この1週間の質問がまだありません</div>
            )}
          </div>
        </div>

        <div className="chart-container" style={{ marginBottom: '12px' }}>
          <div className="chart-header">
            <h3 className="chart-title">
              {sortBy === 'questions' ? '質問数 TOP5 ルーム' : '最新活動 TOP5 ルーム'}
            </h3>
            <div className="chart-sort-buttons">
              <button 
                className={`chart-sort-btn ${sortBy === 'questions' ? 'active' : ''}`}
                onClick={() => setSortBy('questions')}
              >
                質問数順
              </button>
              <button 
                className={`chart-sort-btn ${sortBy === 'recent' ? 'active' : ''}`}
                onClick={() => setSortBy('recent')}
              >
                最新順
              </button>
            </div>
          </div>
          <div className="bar-chart">
            {top5Rooms.map((room) => (
              <div key={room.id} className="bar-chart-row">
                <div className="bar-chart-label" title={room.name}>
                  {room.name}
                </div>
                <div className="bar-chart-bar-container">
                  <div 
                    className="bar-chart-bar"
                    style={{ 
                      width: `${(room.userMessages / maxUserMessages) * 100}%` 
                    }}
                  >
                    <span className="bar-chart-value">
                      {sortBy === 'questions' 
                        ? room.userMessages 
                        : new Date(room.lastActivity).toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' })
                      }
                    </span>
                  </div>
                </div>
              </div>
            ))}
            {top5Rooms.length === 0 && (
              <div className="chart-empty">データがありません</div>
            )}
          </div>
        </div>
      </div>

      <div className="stats-table-container">
        <table className="stats-table">
          <thead>
            <tr>
              <th>ルーム名</th>
              <th>状態</th>
              <th>目標</th>
              <th>質問数</th>
              <th>いいね</th>
              <th>目標設定</th>
              <th>作成日時</th>
              <th>最終活動</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            {roomStats.map((room) => (
              <tr key={room.id} className={room.is_completed ? 'completed-room' : ''}>
                <td 
                  className="room-name clickable"
                  onClick={() => handleRoomClick(room.id)}
                  title="クリックして利用推移を表示"
                >
                  {room.name}
                </td>
                <td>
                  <span className={`status-badge ${room.is_completed ? 'completed' : 'active'}`}>
                    {room.is_completed ? '完了' : '進行中'}
                  </span>
                </td>
                <td 
                  className="goal-text-cell clickable"
                  onClick={() => handleGoalClick(room.id, room.name, room.goal_text, room.goal_id)}
                  title="クリックして目標を編集"
                >
                  {room.goal_text || '目標を設定'}
                </td>
                <td className="user-msg-count">{room.userMessages}</td>
                <td className="liked-count">{room.likedMessages}</td>
                <td className="goal-count">{room.goalCount}</td>
                <td className="date">{formatDate(room.created_at)}</td>
                <td className="date">{formatDate(room.lastActivity)}</td>
                <td>
                  <button
                    className={`btn-toggle-complete ${room.is_completed ? 'completed' : ''}`}
                    onClick={() => toggleRoomCompletion(room.id, room.is_completed)}
                    title={room.is_completed ? '進行中に戻す' : '完了にする'}
                  >
                    {room.is_completed ? '✓' : '○'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {roomStats.length === 0 && (
          <div className="stats-empty">まだルームがありません</div>
        )}
      </div>
      </div>
    </div>
  );
};

export default RoomStats;
