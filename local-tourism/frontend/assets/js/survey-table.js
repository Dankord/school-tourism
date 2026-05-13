const tbody = document.getElementById('tableBody');
const joinedCount = document.getElementById('joinedCount');

    function renderStars(satisfactionValue) {
      const starsCount = Math.max(0, Math.min(5, Math.round(Number(satisfactionValue) || 0)));
      let html = '<span class="stars">';

      for (let i = 1; i <= 5; i++) {
        if (i <= starsCount) {
          html += `<svg class="star" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
          </svg>`;
        } else {
          html += `<svg class="star empty" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
          </svg>`;
        }
      }

      html += '</span>';
      return html;
    }

    function formatRecommendationPercent(value) {
      const numeric = Number(value) || 0;
      return Number.isInteger(numeric) ? `${numeric}%` : `${numeric.toFixed(2)}%`;
    }

    function renderRows(destinations) {
      if (!destinations.length) {
        tbody.innerHTML = `
          <tr>
            <td class="col-destination" colspan="6">No survey responses yet.</td>
          </tr>`;
        return;
      }

      tbody.innerHTML = destinations.map((d) => `
        <tr>
          <td class="col-destination">${d.name}</td>
          <td class="col-students">${d.students}</td>
          <td class="col-recommendation"><span>${formatRecommendationPercent(d.recommendation_percent)}</span></td>
          <td class="col-satisfaction">${renderStars(d.average_maintenance)}</td>
          <td class="col-satisfaction">${renderStars(d.average_understanding)}</td>
          <td class="col-satisfaction">${renderStars(d.average_satisfaction)}</td>
        </tr>
      `).join('');
    }

    function updateJoinedCounter(totalResponses) {
      const count = Number(totalResponses) || 0;
      joinedCount.textContent = `Join ${count.toLocaleString()} students`;
    }

    //fetch from the backend
    async function loadSurveyInsights() {
      try {
        const response = await fetch('../../backend/api/get-response.php', {
          method: 'GET',
          headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
          throw new Error('Failed to fetch survey responses');
        }

        const data = await response.json();
        if (!data.success) {
          throw new Error(data.message || 'Unexpected response');
        }

        renderRows(data.destinations || []);
        updateJoinedCounter(data.summary?.total_responses);
      } catch (error) {
        tbody.innerHTML = `
          <tr>
            <td class="col-destination" colspan="6">Unable to load survey insights right now.</td>
          </tr>`;
        updateJoinedCounter(0);
      }
    }

    loadSurveyInsights();