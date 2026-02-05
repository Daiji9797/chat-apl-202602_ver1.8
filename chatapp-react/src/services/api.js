/**
 * API通信モジュール
 */

const isBrowser = typeof window !== 'undefined' && window.location && window.location.origin;
const DEFAULT_DEV_API_BASE = 'http://localhost:8000/api/';
const DEFAULT_PROD_API_BASE = isBrowser
  ? `${window.location.origin.replace(/\/$/, '')}/api/`
  : '/api/';

export const API_BASE_URL = import.meta.env.VITE_API_URL
  ? `${import.meta.env.VITE_API_URL.replace(/\/$/, '')}/api/`
  : (import.meta.env.PROD ? DEFAULT_PROD_API_BASE : DEFAULT_DEV_API_BASE);

console.log('API_BASE_URL:', API_BASE_URL);

class APIClient {
  constructor() {
    this.token = localStorage.getItem('token');
  }

  setToken(token) {
    console.log('[API] Setting token:', token);
    this.token = token;
    localStorage.setItem('token', token);
    console.log('[API] Token set in localStorage, verification:', localStorage.getItem('token'));
  }

  getToken() {
    const token = localStorage.getItem('token');
    console.log('[API] Getting token from localStorage:', token);
    return token;
  }

  removeToken() {
    localStorage.removeItem('token');
  }

  getHeaders() {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    const token = this.getToken();
    console.log('[API] Token from localStorage:', token ? 'EXISTS' : 'NOT FOUND');
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
      console.log('[API] Authorization header set');
    } else {
      console.log('[API] No token available, request will be unauthorized');
    }

    return headers;
  }

  async request(endpoint, options = {}, retries = 3) {
    const url = API_BASE_URL + endpoint;
    
    for (let attempt = 0; attempt < retries; attempt++) {
      try {
        const response = await fetch(url, {
          headers: this.getHeaders(),
          credentials: 'include', // Cookieとトークンを送信
          ...options,
        });

        const rawText = await response.text();
        console.log('API raw response:', rawText);
        
        let data;
        try {
          data = rawText ? JSON.parse(rawText) : {};
        } catch (err) {
          console.error('API response is not JSON:', rawText);
          console.error('Parse error:', err);
          throw new Error('サーバー応答の解析に失敗しました');
        }

        if (!response.ok) {
          throw new Error(data.message || 'API request failed');
        }
        
        return data;
      } catch (error) {
        // Network errors - retry
        if (error instanceof TypeError && error.message.includes('Failed to fetch')) {
          if (attempt < retries - 1) {
            console.warn(`[API] Request failed (attempt ${attempt + 1}/${retries}), retrying...`, error.message);
            await new Promise(resolve => setTimeout(resolve, 500 * (attempt + 1))); // Exponential backoff
            continue;
          }
        }
        
        // Non-network errors or final attempt - throw
        throw error;
      }
    }
  }

  // 認証関連
  async register(email, password, name = '') {
    return this.request('register', {
      method: 'POST',
      body: JSON.stringify({ email, password, name }),
    });
  }

  async login(email, password) {
    console.log('[API] Attempting login with:', email);
    return this.request('login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  }

  // ルーム関連
  async getRooms(limit = 50, offset = 0) {
    return this.request(`rooms?limit=${limit}&offset=${offset}`, {
      method: 'GET',
    });
  }

  async createRoom(name = 'New Chat') {
    return this.request('rooms', {
      method: 'POST',
      body: JSON.stringify({ name }),
    });
  }

  async getRoom(roomId) {
    return this.request(`rooms/${roomId}`, {
      method: 'GET',
    });
  }

  async updateRoom(roomId, data) {
    return this.request(`rooms/${roomId}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async deleteRoom(roomId) {
    return this.request(`rooms/${roomId}`, {
      method: 'DELETE',
    });
  }

  // チャット関連
  async sendChat(message, roomId, history = [], provider = 'openai') {
    const trimmedHistory = Array.isArray(history) ? history.slice(-20) : [];
    return this.request('chat', {
      method: 'POST',
      body: JSON.stringify({ message, roomId, history: trimmedHistory, provider }),
    });
  }

  async deleteMessage(roomId, messageId) {
    const roomQuery = roomId ? `?roomId=${roomId}` : '';
    return this.request(`messages/${messageId}${roomQuery}`, {
      method: 'DELETE',
    });
  }

  async likeMessage(roomId, messageId, like = true) {
    return this.request('message-likes', {
      method: 'POST',
      body: JSON.stringify({ roomId, messageId, like }),
    });
  }

  // 目標達成メモ
  async createGoalNote(roomId, noteText, messageId = null) {
    return this.request('goals', {
      method: 'POST',
      body: JSON.stringify({ room_id: roomId, note_text: noteText, message_id: messageId }),
    });
  }

  async updateGoalNote(goalId, noteText) {
    return this.request('goals', {
      method: 'PUT',
      body: JSON.stringify({ id: goalId, note_text: noteText }),
    });
  }

  async deleteGoalNote(goalId) {
    return this.request('goals', {
      method: 'DELETE',
      body: JSON.stringify({ id: goalId }),
    });
  }

  async getGoalNotes(roomId = null, limit = 50, offset = 0) {
    const roomQuery = roomId ? `roomId=${roomId}&` : '';
    return this.request(`goals?${roomQuery}limit=${limit}&offset=${offset}`, {
      method: 'GET',
    });
  }

  // 未来Story
  async getStories(roomId = null, storyType = null) {
    let query = 'story?';
    if (roomId) query += `roomId=${roomId}&`;
    if (storyType) query += `storyType=${storyType}&`;
    return this.request(query, {
      method: 'GET',
    });
  }

  async getRoomGoals() {
    return this.request('story/room-goals', {
      method: 'GET',
    });
  }

  async createStory(roomId, noteText, storyDate = null, storyImage = null, imageComment = null) {
    return this.request('story', {
      method: 'POST',
      body: JSON.stringify({ 
        roomId, 
        note_text: noteText, 
        story_date: storyDate,
        story_image: storyImage,
        image_comment: imageComment
      }),
    });
  }

  async updateStory(storyId, noteText = null, storyDate = null, imageComment = null, storyImage = null) {
    return this.request('story', {
      method: 'PUT',
      body: JSON.stringify({ 
        storyId: storyId,
        note_text: noteText,
        story_date: storyDate,
        image_comment: imageComment,
        story_image: storyImage
      }),
    });
  }

  async generateStoryImage(noteText, imageComment = '', provider = 'openai') {
    return this.request('story/generate-image', {
      method: 'POST',
      body: JSON.stringify({ 
        note_text: noteText, 
        image_comment: imageComment,
        provider: provider
      })
    });
  }

  async deleteStory(storyId) {
    return this.request(`story/${storyId}`, {
      method: 'DELETE',
    });
  }

  // アカウント関連
  async getUser() {
    return this.request('user', {
      method: 'GET',
    });
  }

  async deleteAccount(password) {
    return this.request('user', {
      method: 'DELETE',
      body: JSON.stringify({ password }),
    });
  }

  // 今日のテーマランキング
  async getTodayTopics() {
    return this.request('today-topics', {
      method: 'GET',
    });
  }

  // この1週間のテーマランキング
  async getWeeklyTopicsRanking() {
    return this.request('weekly-topics-ranking', {
      method: 'GET',
    });
  }

  // Stalker Image
  async uploadStalkerImage(file) {
    const formData = new FormData();
    formData.append('stalkerImage', file);

    const headers = this.getHeaders();
    delete headers['Content-Type']; // FormData では Content-Type を自動設定

    return this.request('stalker-image', {
      method: 'POST',
      headers,
      body: formData,
    });
  }

  async deleteStalkerImage() {
    return this.request('stalker-image', {
      method: 'DELETE',
    });
  }

  // ガチャ関連
  async getGachaStatus() {
    return this.request('gacha-status', {
      method: 'GET',
    });
  }

  async executeGacha(gacha_type = 0, points_used = 10, gacha_id = 0, result_stage = 1) {
    return this.request('gacha', {
      method: 'POST',
      body: JSON.stringify({
        gacha_type,
        points_used,
        gacha_id,
        result_stage,
      }),
    });
  }

  async getGachaImages() {
    return this.request('gacha-images', {
      method: 'GET',
    });
  }
}

export const api = new APIClient();
